<?php
(function () {
    /***************************************************** Guards *****************************************************/
    if (!defined('TYPO3_REQUESTTYPE')) {
        die('Access denied.');
    }
    if (!class_exists(\In2code\In2publishCore\Service\Context\ContextService::class)) {
        // Early return when installing per ZIP: autoload is not yet generated
        return;
    }

    /*********************************************** Settings/Instances ***********************************************/
    $extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get('in2publish_core');
    $contextService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Service\Context\ContextService::class
    );
    $configContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Config\ConfigContainer::class
    );
    $adapterRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Communication\AdapterRegistry::class
    );

    /************************************************** Init Caching **************************************************/
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = [];
    }

    /************************************************** Init Logging **************************************************/
    $logLevel = $extConf['logLevel'];
    if (version_compare(TYPO3_branch, '10.0', '>=')) {
        $logLevel = \TYPO3\CMS\Core\Log\LogLevel::getInternalName((int)$logLevel);
    }
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
    $configContainer->registerDefiner(\In2code\In2publishCore\Config\Definer\In2publishCoreDefiner::class);
    $configContainer->registerDefiner(
        \In2code\In2publishCore\Features\SimpleOverviewAndAjax\Config\Definer\SimpleOverviewAndAjaxDefiner::class
    );
    $configContainer->registerDefiner(
        \In2code\In2publishCore\Features\WarningOnForeign\Config\Definer\WarningOnForeignDefiner::class
    );
    if ($contextService->isForeign()
        || 'ssh' === $extConf['adapter']['remote']
        || 'ssh' === $extConf['adapter']['transmission']
    ) {
        $configContainer->registerDefiner(\In2code\In2publishCore\Config\Definer\SshConnectionDefiner::class);
    }

    $configContainer->registerProvider(\In2code\In2publishCore\Config\Provider\DefaultProvider::class);
    $configContainer->registerProvider(\In2code\In2publishCore\Config\Provider\FileProvider::class);
    $configContainer->registerProvider(\In2code\In2publishCore\Config\Provider\PageTsProvider::class);
    $configContainer->registerProvider(\In2code\In2publishCore\Config\Provider\VersionedFileProvider::class);
    if (!$extConf['disableUserConfig']) {
        $configContainer->registerProvider(\In2code\In2publishCore\Config\Provider\UserTsProvider::class);
    }

    /******************************************** Configure Compare Plugin ********************************************/
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'In2code.in2publishCore',
        'Pi1',
        ['Frontend' => 'preview'],
        ['Frontend' => 'preview']
    );

    /***************************************** Register Communication Adapter *****************************************/
    $adapterRegistry->registerAdapter(
        \In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\SshAdapter::ADAPTER_TYPE,
        \In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\SshAdapter::ADAPTER_KEY,
        \In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\SshAdapter::class,
        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:adapter.remote.ssh',
        [
            \In2code\In2publishCore\Testing\Tests\SshConnection\SshFunctionAvailabilityTest::class,
            \In2code\In2publishCore\Testing\Tests\SshConnection\SshConnectionTest::class,
        ]
    );
    $adapterRegistry->registerAdapter(
        \In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\SshAdapter::ADAPTER_TYPE,
        \In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\SshAdapter::ADAPTER_KEY,
        \In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\SshAdapter::class,
        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:adapter.transmission.ssh',
        [
            \In2code\In2publishCore\Testing\Tests\SshConnection\SshFunctionAvailabilityTest::class,
            \In2code\In2publishCore\Testing\Tests\SshConnection\SshConnectionTest::class,
            \In2code\In2publishCore\Testing\Tests\SshConnection\SftpRequirementsTest::class,
        ]
    );
})();
