<?php

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\ConfigContainer\Definer\In2publishCoreDefiner;
use In2code\In2publishCore\Component\ConfigContainer\Definer\SshConnectionDefiner;
use In2code\In2publishCore\Component\ConfigContainer\Migration\IngoredFieldsMigration;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\DynamicValueProvider\DynamicValueProviderRegistry;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\DynamicValueProvider\EnvVarProvider;
use In2code\In2publishCore\Component\ConfigContainer\PostProcessor\DynamicValuesPostProcessor;
use In2code\In2publishCore\Component\ConfigContainer\Provider\DefaultProvider;
use In2code\In2publishCore\Component\ConfigContainer\Provider\FileProvider;
use In2code\In2publishCore\Component\ConfigContainer\Provider\PageTsProvider;
use In2code\In2publishCore\Component\ConfigContainer\Provider\UserTsProvider;
use In2code\In2publishCore\Component\ConfigContainer\Provider\VersionedFileProvider;
use In2code\In2publishCore\Component\Core\Record\Model\Extension\RecordExtensionTrait;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter\RemoteAdapterRegistry;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter\SshAdapter as RemoteCommandExecutionSshAdapter;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\SshAdapter as TransmissionSshAdapter;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\TransmissionAdapterRegistry;
use In2code\In2publishCore\Controller\FrontendController;
use In2code\In2publishCore\Features\HideRecordsDeletedDifferently\Config\Definer\HideRecordsDeletedDifferentlyDefiner;
use In2code\In2publishCore\Features\PublishSorting\Config\Definer\PublishSortingDefiner;
use In2code\In2publishCore\Features\RedirectsSupport\Config\Definer\RedirectsSupportDefiner;
use In2code\In2publishCore\Features\WarningOnForeign\Config\Definer\WarningOnForeignDefiner;
use In2code\In2publishCore\Log\Processor\BackendUserProcessor;
use In2code\In2publishCore\Log\Processor\PublishingFailureCollector;
use In2code\In2publishCore\Middleware\BackendRouteInitialization;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Testing\Tests\SshConnection\SftpRequirementsTest;
use In2code\In2publishCore\Testing\Tests\SshConnection\SshConnectionTest;
use In2code\In2publishCore\Testing\Tests\SshConnection\SshFunctionAvailabilityTest;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\DatabaseWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

(static function (): void {
    /***************************************************** Guards *****************************************************/
    if (!defined('TYPO3')) {
        die('Access denied.');
    }
    if (!class_exists(ContextService::class)) {
        // Early return when installing per ZIP: autoload is not yet generated
        return;
    }

    /************************************************* Patching TYPO3 *************************************************/
    // Issue: https://forge.typo3.org/issues/95962
    // Patch: https://review.typo3.org/c/Packages/TYPO3.CMS/+/72160
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Middleware\BackendRouteInitialization::class] = [
        'className' => BackendRouteInitialization::class,
    ];

    /************************************************ Record Extension ************************************************/
    $file = Environment::getVarPath() . '/cache/code/content_publisher/record_extension_trait.php';
    if (file_exists($file)) {
        // Initialize the variable to be able to use it in a reference
        $autoloadFn = null;
        /**
         * This is a one-time autoloader that loads the compiled RecordExtensionTrait instead of the original.
         * To keep the auto-loading overhead low, the function unregisters itself,
         * so it will not interfere with further class loading.
         */
        $autoloadFn = static function (string $class) use (&$autoloadFn, $file): void {
            if (RecordExtensionTrait::class === $class) {
                spl_autoload_unregister($autoloadFn);
                unset($autoloadFn);
                include $file;
            }
        };
        spl_autoload_register($autoloadFn, true, true);
    }

    /*********************************************** Settings/Instances ***********************************************/
    $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('in2publish_core');
    $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
    $remoteAdapterRegistry = GeneralUtility::makeInstance(RemoteAdapterRegistry::class);
    $transmissionAdapterRegistry = GeneralUtility::makeInstance(TransmissionAdapterRegistry::class);
    $dynamicValueProviderRegistry = GeneralUtility::makeInstance(DynamicValueProviderRegistry::class);

    /************************************************** Init Caching **************************************************/
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = [];
    }

    /************************************************** Init Logging **************************************************/
    $logLevel = $extConf['logLevel'];
    $logLevel = LogLevel::getInternalName((int)$logLevel);
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['In2code']['In2publishCore'] = [
        'writerConfiguration' => [
            $logLevel => [
                DatabaseWriter::class => [
                    'logTable' => 'tx_in2publishcore_log',
                ],
            ],
        ],
        'processorConfiguration' => [
            $logLevel => [
                BackendUserProcessor::class => [],
                PublishingFailureCollector::class => [],
            ],
        ],
    ];

    /**************************************** Register Config Definer/Provider ****************************************/
    $configContainer->registerDefiner(In2publishCoreDefiner::class);
    $configContainer->registerDefiner(WarningOnForeignDefiner::class);
    $configContainer->registerDefiner(PublishSortingDefiner::class);
    $configContainer->registerDefiner(SshConnectionDefiner::class);
    $configContainer->registerDefiner(HideRecordsDeletedDifferentlyDefiner::class);

    $configContainer->registerProvider(DefaultProvider::class);
    $configContainer->registerProvider(FileProvider::class);
    $configContainer->registerProvider(PageTsProvider::class);
    $configContainer->registerProvider(VersionedFileProvider::class);
    $configContainer->registerProvider(UserTsProvider::class);

    $configContainer->registerPostProcessor(DynamicValuesPostProcessor::class);

    $configContainer->registerMigration(IngoredFieldsMigration::class);

    $dynamicValueProviderRegistry->registerDynamicValue('env', EnvVarProvider::class);

    /******************************************** Configure Compare Plugin ********************************************/
    ExtensionUtility::configurePlugin(
        'in2publish_core',
        'Pi1',
        [FrontendController::class => 'preview'],
        [FrontendController::class => 'preview'],
    );

    /******************************************** Configure Garbage Collector  ****************************************/
    // Register table tx_in2publishcore_running_request  in table garbage collector
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']['tx_in2publishcore_running_request'] = [
        'dateField' => 'timestamp_begin',
        'expirePeriod' => 1,
    ];

    /***************************************** Register Communication Adapter *****************************************/
    $remoteAdapterRegistry->registerAdapter(
        RemoteCommandExecutionSshAdapter::ADAPTER_KEY,
        RemoteCommandExecutionSshAdapter::class,
        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:adapter.remote.ssh',
        [
            SshFunctionAvailabilityTest::class,
            SshConnectionTest::class,
        ],
    );
    $transmissionAdapterRegistry->registerAdapter(
        TransmissionSshAdapter::ADAPTER_KEY,
        TransmissionSshAdapter::class,
        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:adapter.transmission.ssh',
        [
            SshFunctionAvailabilityTest::class,
            SshConnectionTest::class,
            SftpRequirementsTest::class,
        ],
    );

    /************************************************ Redirect Support ************************************************/
    $configContainer->registerDefiner(RedirectsSupportDefiner::class);
})();
