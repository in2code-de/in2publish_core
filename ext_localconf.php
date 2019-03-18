<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function () {
        // @codingStandardsIgnoreStart @formatter:off
        $extConf = [
            'disableUserConfig' => false,
            'adapter.' => [
                'remote' => 'ssh',
                'transmission' => 'ssh',
            ]
        ];
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['in2publish_core'])) {
            if (is_array($setConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['in2publish_core']))) {
                \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($extConf, $setConf);
            }
        }
        if (!class_exists(\In2code\In2publishCore\Service\Context\ContextService::class)) {
            // Early return when installing per ZIP: autoload is not yet generated
            return;
        }
        $contextService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Service\Context\ContextService::class);


        /************************************************ Cache Config ************************************************/
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = [];
        }


        /************************************** Register Config Provider/Definer **************************************/
        $configContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Config\ConfigContainer::class);

        $configContainer->registerDefiner(\In2code\In2publishCore\Config\Definer\In2publishCoreDefiner::class);
        $configContainer->registerDefiner(\In2code\In2publishCore\Features\SimpleOverviewAndAjax\Config\Definer\SimpleOverviewAndAjaxDefiner::class);
        if ($contextService->isForeign() || ('ssh' === $extConf['adapter.']['remote'] || 'ssh' === $extConf['adapter.']['transmission'])) {
            $configContainer->registerDefiner(\In2code\In2publishCore\Config\Definer\SshConnectionDefiner::class);
        }
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('realurl')) {
            $configContainer->registerDefiner(\In2code\In2publishCore\Features\RealUrlSupport\Config\Definer\RealUrlDefiner::class);
        }

        $configContainer->registerProvider(\In2code\In2publishCore\Config\Provider\DefaultProvider::class);
        $configContainer->registerProvider(\In2code\In2publishCore\Config\Provider\FileProvider::class);
        $configContainer->registerProvider(\In2code\In2publishCore\Config\Provider\PageTsProvider::class);
        $configContainer->registerProvider(\In2code\In2publishCore\Config\Provider\VersionedFileProvider::class);
        if (!$extConf['disableUserConfig']) {
            $configContainer->registerProvider(\In2code\In2publishCore\Config\Provider\UserTsProvider::class);
        }


        /****************************************** Configure Compare Plugin ******************************************/
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'In2code.in2publishCore',
            'Pi1',
            ['Frontend' => 'preview'],
            ['Frontend' => 'preview']
        );


        /*********************************** Register Communication Adapter ***********************************/
        $adapterRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Communication\AdapterRegistry::class);
        $adapterRegistry->registerAdapter(
            'remote',
            'ssh',
            \In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\SshAdapter::class,
            'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:adapter.remote.ssh',
            [
                \In2code\In2publishCore\Testing\Tests\SshConnection\SshFunctionAvailabilityTest::class,
                \In2code\In2publishCore\Testing\Tests\SshConnection\SshConnectionTest::class,
            ]
        );
        $adapterRegistry->registerAdapter(
            'transmission',
            'ssh',
            \In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\SshAdapter::class,
            'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:adapter.transmission.ssh',
            [
                \In2code\In2publishCore\Testing\Tests\SshConnection\SshFunctionAvailabilityTest::class,
                \In2code\In2publishCore\Testing\Tests\SshConnection\SshConnectionTest::class,
                \In2code\In2publishCore\Testing\Tests\SshConnection\SftpRequirementsTest::class,
            ]
        );
        // @codingStandardsIgnoreEnd @formatter:on
    }
);
