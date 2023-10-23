<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

(static function () {
    /***************************************************** Guards *****************************************************/
    if (!defined('TYPO3_REQUESTTYPE')) {
        die('Access denied.');
    }
    if (!class_exists(\In2code\In2publishCore\Service\Context\ContextService::class)) {
        // Early return when installing per ZIP: autoload is not yet generated
        return;
    }

    /************************************************* Patching TYPO3 *************************************************/
    // Issue: https://forge.typo3.org/issues/95962
    // Patch: https://review.typo3.org/c/Packages/TYPO3.CMS/+/72160
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Middleware\BackendRouteInitialization::class] = [
        'className' => \In2code\In2publishCore\Middleware\BackendRouteInitialization::class,
    ];

    /************************************************ Record Extension ************************************************/
    $file = \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/cache/code/content_publisher/record_extension_trait.php';
    if (file_exists($file)) {
        // Initialize the variable to be able to use it in a reference
        $autoloadFn = null;
        /**
         * This is a one-time autoloader that loads the compiled RecordExtensionTrait instead of the original.
         * To keep the auto-loading overhead low, the function unregisters itself,
         * so it will not interfere with further class loading.
         */
        $autoloadFn = static function (string $class) use (&$autoloadFn, $file): void {
            if (\In2code\In2publishCore\Component\Core\Record\Model\Extension\RecordExtensionTrait::class === $class) {
                spl_autoload_unregister($autoloadFn);
                unset($autoloadFn);
                include $file;
            }
        };
        spl_autoload_register($autoloadFn, true, true);
    }

    /*********************************************** Settings/Instances ***********************************************/
    $extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class,
    )->get('in2publish_core');
    $contextService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Service\Context\ContextService::class,
    );
    $configContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Component\ConfigContainer\ConfigContainer::class,
    );
    $remoteAdapterRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter\RemoteAdapterRegistry::class,
    );
    $transmissionAdapterRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\TransmissionAdapterRegistry::class,
    );

    /************************************************** Init Caching **************************************************/
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = [];
    }

    /************************************************** Init Logging **************************************************/
    $logLevel = $extConf['logLevel'];
    $logLevel = \TYPO3\CMS\Core\Log\LogLevel::getInternalName((int)$logLevel);
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['In2code']['In2publishCore'] = [
        'writerConfiguration' => [
            $logLevel => [
                \TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => [
                    'logTable' => 'tx_in2publishcore_log',
                ],
            ],
        ],
        'processorConfiguration' => [
            $logLevel => [
                \In2code\In2publishCore\Log\Processor\BackendUserProcessor::class => [],
                \In2code\In2publishCore\Log\Processor\PublishingFailureCollector::class => [],
            ],
        ],
    ];

    /**************************************** Register Config Definer/Provider ****************************************/
    $configContainer->registerDefiner(\In2code\In2publishCore\Component\ConfigContainer\Definer\In2publishCoreDefiner::class);
    $configContainer->registerDefiner(
        \In2code\In2publishCore\Features\WarningOnForeign\Config\Definer\WarningOnForeignDefiner::class,
    );
    $configContainer->registerDefiner(
        \In2code\In2publishCore\Features\PublishSorting\Config\Definer\PublishSortingDefiner::class,
    );
    if (
        $contextService->isForeign()
        || 'ssh' === $remoteAdapterRegistry->getSelectedAdapter()
        || 'ssh' === $transmissionAdapterRegistry->getSelectedAdapter()
    ) {
        $configContainer->registerDefiner(\In2code\In2publishCore\Component\ConfigContainer\Definer\SshConnectionDefiner::class);
    }
    $configContainer->registerDefiner(\In2code\In2publishCore\Features\HideRecordsDeletedDifferently\Config\Definer\HideRecordsDeletedDifferentlyDefiner::class);

    $configContainer->registerProvider(\In2code\In2publishCore\Component\ConfigContainer\Provider\DefaultProvider::class);
    $configContainer->registerProvider(\In2code\In2publishCore\Component\ConfigContainer\Provider\FileProvider::class);
    $configContainer->registerProvider(\In2code\In2publishCore\Component\ConfigContainer\Provider\PageTsProvider::class);
    $configContainer->registerProvider(\In2code\In2publishCore\Component\ConfigContainer\Provider\VersionedFileProvider::class);
    if (!$extConf['disableUserConfig']) {
        $configContainer->registerProvider(\In2code\In2publishCore\Component\ConfigContainer\Provider\UserTsProvider::class);
    }
    $configContainer->registerPostProcessor(
        \In2code\In2publishCore\Component\ConfigContainer\PostProcessor\DynamicValuesPostProcessor::class,
    );

    $dynamicValueProviderRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Component\ConfigContainer\PostProcessor\DynamicValueProvider\DynamicValueProviderRegistry::class,
    );
    $dynamicValueProviderRegistry->registerDynamicValue(
        'env',
        \In2code\In2publishCore\Component\ConfigContainer\PostProcessor\DynamicValueProvider\EnvVarProvider::class,
    );

    $configContainer->registerMigration(
        \In2code\In2publishCore\Component\ConfigContainer\Migration\IngoredFieldsMigration::class);


    /******************************************** Configure Compare Plugin ********************************************/
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'in2publish_core',
        'Pi1',
        [\In2code\In2publishCore\Controller\FrontendController::class => 'preview'],
        [\In2code\In2publishCore\Controller\FrontendController::class => 'preview'],
    );

    /******************************************** Configure Garbage Collector  ****************************************/
    // Register table tx_in2publishcore_running_request  in table garbage collector
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['tx_in2publishcore_running_request'] = [
        'dateField' => 'timestamp_begin',
        'expirePeriod' => 1,
    ];

    /***************************************** Register Communication Adapter *****************************************/
    $remoteAdapterRegistry->registerAdapter(
        \In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter\SshAdapter::ADAPTER_KEY,
        \In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter\SshAdapter::class,
        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:adapter.remote.ssh',
        [
            \In2code\In2publishCore\Testing\Tests\SshConnection\SshFunctionAvailabilityTest::class,
            \In2code\In2publishCore\Testing\Tests\SshConnection\SshConnectionTest::class,
        ],
    );
    $transmissionAdapterRegistry->registerAdapter(
        \In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\SshAdapter::ADAPTER_KEY,
        \In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\SshAdapter::class,
        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:adapter.transmission.ssh',
        [
            \In2code\In2publishCore\Testing\Tests\SshConnection\SshFunctionAvailabilityTest::class,
            \In2code\In2publishCore\Testing\Tests\SshConnection\SshConnectionTest::class,
            \In2code\In2publishCore\Testing\Tests\SshConnection\SftpRequirementsTest::class,
        ],
    );

    /************************************************ Redirect Support ************************************************/
    $configContainer->registerDefiner(
        \In2code\In2publishCore\Features\RedirectsSupport\Config\Definer\RedirectsSupportDefiner::class,
    );
})();
