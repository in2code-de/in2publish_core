<?php

call_user_func(
    function () {
        /**
         * Manually load Spy YAML parser
         */
        if (!class_exists('\Spyc')) {
            $file = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
                'in2publish_core',
                'Resources/Private/Libraries/Spyc/Spyc.php'
            );
            require_once($file);
        }

        // Enable caching
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = array();

        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
            'In2code.in2publishCore',
            'Pi1',
            array(
                'Frontend' => 'preview',
                'Notification' => 'getNotifications',
            ),
            array(
                'Frontend' => 'preview',
                'Notification' => 'getNotifications',
            )
        );
    }
);
