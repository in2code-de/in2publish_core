<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function () {
        // @codingStandardsIgnoreStart @formatter:off

        $extConf = [
            'disableUserConfig' => false,
        ];
        $setConf = @unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['in2publish_core']);
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($extConf, $setConf);

        /************************************************ Cache Config ************************************************/
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'])) {
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = [];
        }


        /************************************** Register Config Provider/Definer **************************************/
        $configContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Config\ConfigContainer::class);

        $configContainer->registerDefiner(\In2code\In2publishCore\Config\Definer\In2publishCoreDefiner::class);
        $configContainer->registerDefiner(\In2code\In2publishCore\Config\Definer\SshConnectionDefiner::class);
        $configContainer->registerDefiner(\In2code\In2publishCore\Features\RealUrlSupport\Config\Definer\RealUrlDefiner::class);

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
        // @codingStandardsIgnoreEnd @formatter:on
    }
);
