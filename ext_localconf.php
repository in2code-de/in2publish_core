<?php

call_user_func(
    function () {
        // Manually load Spy YAML parser
        if (!class_exists(\Spyc::class)) {
            $file = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
                'in2publish_core',
                'Resources/Private/Libraries/Spyc/Spyc.php'
            );
            require_once($file);
        }

        // Enable caching
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = [];

        // register preview plugin
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'In2code.in2publishCore',
            'Pi1',
            ['Frontend' => 'preview'],
            ['Frontend' => 'preview']
        );
    }
);
