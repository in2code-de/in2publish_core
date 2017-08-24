<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function ($extKey) {
        if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
            $contextService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \In2code\In2publishCore\Service\Context\ContextService::class
            );

            // Manually load Spy YAML parser
            if (!class_exists(\Spyc::class)) {
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

                $GLOBALS['TYPO3_CONF_VARS']['LOG']['In2code']['In2publishCore'] = [
                    'writerConfiguration' => [
                        $logLevel => [
                            \TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => [
                                'logTable' => 'tx_in2code_in2publish_log',
                            ],
                        ],
                    ],
                    'processorConfiguration' => [
                        $logLevel => [
                            \In2code\In2publishCore\Log\Processor\BackendUserProcessor::class => [],
                        ],
                    ],
                ];
            }

            // These command controllers are always available.
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
                \In2code\In2publishCore\Command\StatusCommandController::class;
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
                \In2code\In2publishCore\Command\EnvironmentCommandController::class;
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
                \In2code\In2publishCore\Command\TableCommandController::class;

            /**
             * On foreign environment
             */
            if ($contextService->isForeign()) {
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
                    \In2code\In2publishCore\Command\PublishTasksRunnerCommandController::class;
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] =
                    \In2code\In2publishCore\Command\RpcCommandController::class;
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
                        [
                            'Record' => 'index,detail,publishRecord,publishRecordRecursive,toggleFilterStatusAndRedirectToIndex',
                        ],
                        [
                            'access' => 'user,group',
                            'icon' => 'EXT:' . $extKey . '/Resources/Public/Icons/Record.svg',
                            'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_mod1.xlf',
                        ]
                    );
                }

                // Register file publishing module
                if ($isModuleM3Enabled) {
                    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                        'In2code.' . $extKey,
                        'file',
                        'm3',
                        '',
                        [
                            'File' => 'index,publishFolder,publishFile,toggleFilterStatusAndRedirectToIndex',
                        ],
                        [
                            'access' => 'user,group',
                            'icon' => 'EXT:' . $extKey . '/Resources/Public/Icons/File.svg',
                            'labels' => 'LLL:EXT:' . $extKey . '/Resources/Private/Language/locallang_mod3.xlf',
                        ]
                    );
                }

                // Register Tools module
                // check explicitly against false to enable this module when the configuration could not be read
                if ($isModuleM4Enabled !== false) {
                    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['extTablesInclusion-PostProcessing'][] = \In2code\In2publishCore\Tools\ToolsRegistry::class;
                    $toolsRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        \In2code\In2publishCore\Tools\ToolsRegistry::class
                    );
                    $toolsRegistry->addTool(
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.index',
                        '',
                        'Tools',
                        'index'
                    );
                    $toolsRegistry->addTool(
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.test',
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.test.description',
                        'Tools',
                        'test'
                    );
                    $toolsRegistry->addTool(
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.configuration',
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.configuration.description',
                        'Tools',
                        'configuration'
                    );
                    $toolsRegistry->addTool(
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.show_logs',
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.show_logs.description',
                        'Tools',
                        'showLogs'
                    );
                    $toolsRegistry->addTool(
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.tca',
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.tca.description',
                        'Tools',
                        'tca'
                    );
                    $toolsRegistry->addTool(
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_tca',
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_tca.description',
                        'Tools',
                        'clearTcaCaches'
                    );
                    $toolsRegistry->addTool(
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_registry',
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_registry.description',
                        'Tools',
                        'flushRegistry'
                    );
                    $letterbox = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                        \In2code\In2publishCore\Communication\RemoteProcedureCall\Letterbox::class
                    );
                    if ($letterbox->hasUnAnsweredEnvelopes()) {
                        $toolsRegistry->addTool(
                            'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_envelopes',
                            'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_envelopes.description',
                            'Tools',
                            'flushEnvelopes'
                        );
                    }
                }

                // Register Anomalies
                /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
                $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class
                );
                $signalSlotDispatcher->connect(
                    \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
                    'publishRecordRecursiveAfterPublishing',
                    \In2code\In2publishCore\Domain\Anomaly\PhysicalFilePublisher::class,
                    'publishPhysicalFileOfSysFile',
                    false
                );
                $signalSlotDispatcher->connect(
                    \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
                    'publishRecordRecursiveBeforePublishing',
                    \In2code\In2publishCore\Domain\Anomaly\CacheInvalidator::class,
                    'registerClearCacheTasks',
                    false
                );
                $signalSlotDispatcher->connect(
                    \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
                    'publishRecordRecursiveEnd',
                    \In2code\In2publishCore\Domain\Anomaly\CacheInvalidator::class,
                    'writeClearCacheTask',
                    false
                );
                $signalSlotDispatcher->connect(
                    \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
                    'publishRecordRecursiveAfterPublishing',
                    \In2code\In2publishCore\Domain\Anomaly\RealUrlCacheInvalidator::class,
                    'registerClearRealUrlCacheTask',
                    false
                );
                $signalSlotDispatcher->connect(
                    \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
                    'publishRecordRecursiveAfterPublishing',
                    \In2code\In2publishCore\Domain\Anomaly\SysLogPublisher::class,
                    'publishSysLog',
                    false
                );

                $reserveSysFileUids = \In2code\In2publishCore\Utility\ConfigurationUtility::getConfiguration(
                    'factory.fal.reserveSysFileUids'
                );
                if (false === $reserveSysFileUids) {
                    $indexPostProcessor = \In2code\In2publishCore\Domain\PostProcessing\FalIndexPostProcessor::class;
                } else {
                    $indexPostProcessor = \In2code\In2publishCore\Domain\PostProcessing\FileIndexPostProcessor::class;
                }

                // check if value is explicit false. after updating it's "null" if not set
                $signalSlotDispatcher->connect(
                    \In2code\In2publishCore\Domain\Factory\RecordFactory::class,
                    'instanceCreated',
                    $indexPostProcessor,
                    'registerInstance',
                    false
                );
                $signalSlotDispatcher->connect(
                    \In2code\In2publishCore\Domain\Factory\RecordFactory::class,
                    'rootRecordFinished',
                    $indexPostProcessor,
                    'postProcess',
                    false
                );

                // register tests for tools module
                $GLOBALS['in2publish_core']['tests'] = [
                    \In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationIsAvailableTest::class,
                    \In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationFormatTest::class,
                    \In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationValuesTest::class,
                    \In2code\In2publishCore\Testing\Tests\Database\LocalDatabaseTest::class,
                    \In2code\In2publishCore\Testing\Tests\Database\ForeignDatabaseTest::class,
                    \In2code\In2publishCore\Testing\Tests\Database\DatabaseDifferencesTest::class,
                    \In2code\In2publishCore\Testing\Tests\SshConnection\SshFunctionAvailabilityTest::class,
                    \In2code\In2publishCore\Testing\Tests\SshConnection\SshConnectionTest::class,
                    \In2code\In2publishCore\Testing\Tests\SshConnection\SftpRequirementsTest::class,
                    \In2code\In2publishCore\Testing\Tests\Application\LocalInstanceTest::class,
                    \In2code\In2publishCore\Testing\Tests\Application\LocalSysDomainTest::class,
                    \In2code\In2publishCore\Testing\Tests\Application\ForeignInstanceTest::class,
                    \In2code\In2publishCore\Testing\Tests\Application\ForeignSysDomainTest::class,
                    \In2code\In2publishCore\Testing\Tests\Fal\MissingStoragesTest::class,
                    \In2code\In2publishCore\Testing\Tests\Fal\CaseSensitivityTest::class,
                    \In2code\In2publishCore\Testing\Tests\Fal\IdenticalDriverTest::class,
                    \In2code\In2publishCore\Testing\Tests\Fal\UniqueStorageTargetTest::class,
                ];
            }
        }
    },
    $_EXTKEY
);
