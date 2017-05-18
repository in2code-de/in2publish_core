<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function ($extKey) {
        if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
            $contextService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                'In2code\\In2publishCore\\Service\\Context\\ContextService'
            );

            // Manually load Spy YAML parser
            if (!class_exists('\Spyc')) {
                $file = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
                    'in2publish_core',
                    'Resources/Private/Libraries/Spyc/Spyc.php'
                );
                require_once($file);
            }

            $isModuleM1Enabled = \In2code\In2publishCore\Utility\ConfigurationUtility::getConfiguration('module.m1');
            $isModuleM3Enabled = \In2code\In2publishCore\Utility\ConfigurationUtility::getConfiguration('module.m3');
            $isModuleM4Enabled = \In2code\In2publishCore\Utility\ConfigurationUtility::getConfiguration('module.m4');

            // initialize logging with configuration
            if (\In2code\In2publishCore\Utility\ConfigurationUtility::isConfigurationLoadedSuccessfully()) {
                $logConfiguration = \In2code\In2publishCore\Utility\ConfigurationUtility::getConfiguration('log');

                if (isset($logConfiguration['logLevel'])) {
                    $logLevel = $logConfiguration['logLevel'];
                    $logLevel = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($logLevel, 0, 7, 5);
                } else {
                    $logLevel = 5;
                }

                $GLOBALS['TYPO3_CONF_VARS']['LOG']['In2code']['In2publishCore'] = array(
                    'writerConfiguration' => array(
                        $logLevel => array(
                            'TYPO3\\CMS\\Core\\Log\\Writer\\DatabaseWriter' => array(
                                'logTable' => 'tx_in2code_in2publish_log',
                            ),
                        ),
                    ),
                    'processorConfiguration' => array(
                        $logLevel => array(
                            'In2code\\In2publishCore\\Log\\Processor\\BackendUserProcessor' => array(),
                        ),
                    ),
                );
            }

            // These command controllers are always available.
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
                'In2code\\In2publishCore\\Command\\StatusCommandController';
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
                'In2code\\In2publishCore\\Command\\EnvironmentCommandController';
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
                'In2code\\In2publishCore\\Command\\TableCommandController';

            /**
             * On foreign environment
             */
            if ($contextService->isForeign()) {
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
                    'In2code\\In2publishCore\\Command\\PublishTasksRunnerCommandController';
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
                    'In2code\\In2publishCore\\Command\\RpcCommandController';
            }

            /**
             * On local environment
             */
            if ($contextService->isLocal()) {
                // Register record publishing module
                if ($isModuleM1Enabled) {
                    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                        'In2code.' . $extKey,
                        'web',
                        'm1',
                        '',
                        array(
                            'Record' => 'index,detail,publishRecord,publishRecordRecursive,toggleFilterStatusAndRedirectToIndex',
                        ),
                        array(
                            'access' => 'user,group',
                            'icon' => 'EXT:' . $extKey . '/Resources/Public/Icons/Record.svg',
                            'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_mod1.xlf',
                        )
                    );
                }

                // Register file publishing module
                if ($isModuleM3Enabled) {
                    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                        'In2code.' . $extKey,
                        'file',
                        'm3',
                        '',
                        array(
                            'File' => 'index,publishFolder,publishFile,toggleFilterStatusAndRedirectToIndex',
                        ),
                        array(
                            'access' => 'user,group',
                            'icon' => 'EXT:' . $extKey . '/Resources/Public/Icons/File.svg',
                            'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_mod3.xlf',
                        )
                    );
                }

                // Register Tools module
                // check explicitly against false to enable this module when the configuration could not be read
                if ($isModuleM4Enabled !== false) {
                    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                        'In2code.' . $extKey,
                        'tools',
                        'm4',
                        '',
                        array(
                            'Tools' => 'index, test, showLogs, flushLogs, configuration, tca, clearTcaCaches, flushEnvelopes, flushRegistry',
                        ),
                        array(
                            'access' => 'admin',
                            'icon' => 'EXT:' . $extKey . '/Resources/Public/Icons/Tools.svg',
                            'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_mod4.xlf',
                        )
                    );
                }

                // Register Anomalies
                /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
                $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    'TYPO3\CMS\Extbase\SignalSlot\Dispatcher'
                );
                $signalSlotDispatcher->connect(
                    'In2code\\In2publishCore\\Domain\\Repository\\CommonRepository',
                    'publishRecordRecursiveAfterPublishing',
                    'In2code\\In2publishCore\\Domain\\Anomaly\\PhysicalFilePublisher',
                    'publishPhysicalFileOfSysFile',
                    false
                );
                $signalSlotDispatcher->connect(
                    'In2code\\In2publishCore\\Domain\\Repository\\CommonRepository',
                    'publishRecordRecursiveBeforePublishing',
                    'In2code\\In2publishCore\\Domain\\Anomaly\\CacheInvalidator',
                    'registerClearCacheTasks',
                    false
                );
                $signalSlotDispatcher->connect(
                    'In2code\\In2publishCore\\Domain\\Repository\\CommonRepository',
                    'publishRecordRecursiveEnd',
                    'In2code\\In2publishCore\\Domain\\Anomaly\\CacheInvalidator',
                    'writeClearCacheTask',
                    false
                );
                $signalSlotDispatcher->connect(
                    'In2code\\In2publishCore\\Domain\\Repository\\CommonRepository',
                    'publishRecordRecursiveAfterPublishing',
                    'In2code\\In2publishCore\\Domain\\Anomaly\\RealUrlCacheInvalidator',
                    'registerClearRealUrlCacheTask',
                    false
                );
                $signalSlotDispatcher->connect(
                    'In2code\\In2publishCore\\Domain\\Repository\\CommonRepository',
                    'publishRecordRecursiveAfterPublishing',
                    'In2code\\In2publishCore\\Domain\\Anomaly\\SysLogPublisher',
                    'publishSysLog',
                    false
                );

                if (false === \In2code\In2publishCore\Utility\ConfigurationUtility::getConfiguration('factory.fal.reserveSysFileUids')) {
                    $indexPostProcessor = 'In2code\\In2publishCore\\Domain\\PostProcessing\\FalIndexPostProcessor';
                } else {
                    $indexPostProcessor = 'In2code\\In2publishCore\\Domain\\PostProcessing\\FileIndexPostProcessor';
                }

                // check if value is explicit false. after updating it's "null" if not set
                $signalSlotDispatcher->connect(
                    'In2code\\In2publishCore\\Domain\\Factory\\RecordFactory',
                    'instanceCreated',
                    $indexPostProcessor,
                    'registerInstance',
                    false
                );
                $signalSlotDispatcher->connect(
                    'In2code\\In2publishCore\\Domain\\Factory\\RecordFactory',
                    'rootRecordFinished',
                    $indexPostProcessor,
                    'postProcess',
                    false
                );

                // register tests for tools module
                $GLOBALS['in2publish_core']['tests'] = array(
                    'In2code\\In2publishCore\\Testing\\Tests\\Configuration\\ConfigurationIsAvailableTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Configuration\\ConfigurationFormatTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Configuration\\ConfigurationValuesTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Database\\LocalDatabaseTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Database\\ForeignDatabaseTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Database\\DatabaseDifferencesTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\SshConnection\\SshFunctionAvailabilityTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\SshConnection\\SshConnectionTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Application\\LocalInstanceTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Application\\LocalSysDomainTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Application\\ForeignInstanceTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Application\\ForeignSysDomainTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Fal\\MissingStoragesTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Fal\\CaseSensitivityTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Fal\\IdenticalDriverTest',
                    'In2code\\In2publishCore\\Testing\\Tests\\Fal\\UniqueStorageTargetTest',
                );
            }
        }
    },
    $_EXTKEY
);
