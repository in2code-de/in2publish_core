<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function () {
        // @codingStandardsIgnoreStart @formatter:off
        // abort if not in BE or INSTALL TOOL
        if (TYPO3_MODE !== 'BE' || TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL) {
            return;
        }

        $extConf = [
            'logLevel' => 5,
        ];
        $setConf = @unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['in2publish_core']);
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($extConf, $setConf);


        /************************************************ Init Logging ************************************************/
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['In2code']['In2publishCore'] = [
            'writerConfiguration' => [$extConf['logLevel'] => [\TYPO3\CMS\Core\Log\Writer\DatabaseWriter::class => ['logTable' => 'tx_in2code_in2publish_log']]],
            'processorConfiguration' => [$extConf['logLevel'] => [\In2code\In2publishCore\Log\Processor\BackendUserProcessor::class => []]],
        ];


        /*********************************** register basic command controllers ***********************************/
        $configContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Config\ConfigContainer::class);
        $contextService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Service\Context\ContextService::class);
        $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/In2publishCore/BackendModule');
        $pageRenderer->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('in2publish_core') . 'Resources/Public/Css/Modules.css');


        /*********************************** register basic command controllers ***********************************/
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \In2code\In2publishCore\Command\StatusCommandController::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \In2code\In2publishCore\Command\EnvironmentCommandController::class;
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \In2code\In2publishCore\Command\TableCommandController::class;


        /********************************************** ONLY FOREIGN **********************************************/
        if ($contextService->isForeign()) {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \In2code\In2publishCore\Command\PublishTasksRunnerCommandController::class;
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \In2code\In2publishCore\Command\RpcCommandController::class;
        }


        /*********************************************** ONLY LOCAL ***********************************************/
        if ($contextService->isLocal()) {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \In2code\In2publishCore\Command\ToolsCommandController::class;

            if ($configContainer->get('module.m1')) {
                \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                    'In2code.In2publishCore',
                    'web',
                    'm1',
                    '',
                    [
                        'Record' => 'index,detail,publishRecord,publishRecordRecursive,toggleFilterStatusAndRedirectToIndex',
                    ],
                    [
                        'access' => 'user,group',
                        'icon' => 'EXT:in2publish_core/Resources/Public/Icons/Record.svg',
                        'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod1.xlf',
                    ]
                );
            }

            if ($configContainer->get('module.m3')) {
                \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                    'In2code.In2publishCore',
                    'file',
                    'm3',
                    '',
                    [
                        'File' => 'index,publishFolder,publishFile,toggleFilterStatusAndRedirectToIndex',
                    ],
                    [
                        'access' => 'user,group',
                        'icon' => 'EXT:in2publish_core/Resources/Public/Icons/File.svg',
                        'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod3.xlf',
                    ]
                );
            }


            /********************************** register tools for the tools mod **********************************/
            if ($configContainer->get('module.m4') !== false) {
                $letterbox = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Communication\RemoteProcedureCall\Letterbox::class);

                $toolsRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\In2code\In2publishCore\Tools\ToolsRegistry::class);
                $toolsRegistry->addTool('LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.index', '', 'Tools', 'index');
                $toolsRegistry->addTool('LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.test', 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.test.description', 'Tools', 'test');
                $toolsRegistry->addTool('LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.configuration', 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.configuration.description', 'Tools', 'configuration');
                if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('logs')) {
                    $toolsRegistry->addTool('LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.advanced_logs', 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.advanced_logs.description', 'Log', 'filter,delete');
                } else {
                    $toolsRegistry->addTool('LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.show_logs', 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.show_logs.description', 'Tools', 'showLogs,flushLogs');
                }
                $toolsRegistry->addTool('LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.tca', 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.tca.description', 'Tools', 'tca');
                $toolsRegistry->addTool('LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_tca', 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_tca.description', 'Tools', 'clearTcaCaches');
                $toolsRegistry->addTool('LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_registry', 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_registry.description', 'Tools', 'flushRegistry');
                if ($letterbox->hasUnAnsweredEnvelopes()) {
                    $toolsRegistry->addTool('LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_envelopes', 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_envelopes.description', 'Tools', 'flushEnvelopes');
                }
            }


            /**************************************** Anomaly Registration ****************************************/
            /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
            $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
            $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Repository\CommonRepository::class, 'publishRecordRecursiveBeforePublishing', \In2code\In2publishCore\Features\CacheInvalidation\Domain\Anomaly\CacheInvalidator::class, 'registerClearCacheTasks', false);
            $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Repository\CommonRepository::class, 'publishRecordRecursiveAfterPublishing', \In2code\In2publishCore\Domain\Anomaly\PhysicalFilePublisher::class, 'publishPhysicalFileOfSysFile', false);
            $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Repository\CommonRepository::class, 'publishRecordRecursiveAfterPublishing', \In2code\In2publishCore\Domain\Anomaly\SysLogPublisher::class, 'publishSysLog', false);
            $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Repository\CommonRepository::class, 'publishRecordRecursiveAfterPublishing', \In2code\In2publishCore\Domain\Anomaly\RefindexUpdater::class, 'registerRefindexUpdate', false);
            $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Repository\CommonRepository::class, 'publishRecordRecursiveEnd', \In2code\In2publishCore\Domain\Anomaly\RefindexUpdater::class, 'writeRefindexUpdateTask', false);
            $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Repository\CommonRepository::class, 'publishRecordRecursiveEnd', \In2code\In2publishCore\Features\CacheInvalidation\Domain\Anomaly\CacheInvalidator::class, 'writeClearCacheTask', false);
            if (!$configContainer->get('factory.fal.reserveSysFileUids')) {
                $indexPostProcessor = \In2code\In2publishCore\Domain\PostProcessing\FalIndexPostProcessor::class;
            } else {
                $indexPostProcessor = \In2code\In2publishCore\Domain\PostProcessing\FileIndexPostProcessor::class;
            }
            $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Factory\RecordFactory::class, 'instanceCreated', $indexPostProcessor, 'registerInstance', false);
            $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Factory\RecordFactory::class, 'rootRecordFinished', $indexPostProcessor, 'postProcess', false);

            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
                $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Repository\CommonRepository::class, 'publishRecordRecursiveBeforePublishing', \In2code\In2publishCore\Features\NewsSupport\Domain\Anomaly\NewsCacheInvalidator::class, 'registerClearCacheTasks', false);
                $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Repository\CommonRepository::class, 'publishRecordRecursiveEnd', \In2code\In2publishCore\Features\NewsSupport\Domain\Anomaly\NewsCacheInvalidator::class, 'writeClearCacheTask', false);
            }
            if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('realurl')) {
                $signalSlotDispatcher->connect(\In2code\In2publishCore\Domain\Repository\CommonRepository::class, 'publishRecordRecursiveAfterPublishing', \In2code\In2publishCore\Features\RealUrlSupport\Domain\Anomaly\RealUrlCacheInvalidator::class, 'registerClearRealUrlCacheTask', false);
            }

            /***************************************** Tests Registration *****************************************/
            $GLOBALS['in2publish_core']['tests'] = [
                \In2code\In2publishCore\Testing\Tests\Adapter\AdapterSelectionTest::class,
                \In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationFormatTest::class,
                \In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationValuesTest::class,
                \In2code\In2publishCore\Testing\Tests\Database\LocalDatabaseTest::class,
                \In2code\In2publishCore\Testing\Tests\Database\ForeignDatabaseTest::class,
                \In2code\In2publishCore\Testing\Tests\Database\DatabaseDifferencesTest::class,
                \In2code\In2publishCore\Testing\Tests\Adapter\RemoteAdapterTest::class,
                \In2code\In2publishCore\Testing\Tests\Adapter\TransmissionAdapterTest::class,
                \In2code\In2publishCore\Testing\Tests\Application\LocalInstanceTest::class,
                \In2code\In2publishCore\Testing\Tests\Application\LocalSysDomainTest::class,
                \In2code\In2publishCore\Testing\Tests\Application\ForeignInstanceTest::class,
                \In2code\In2publishCore\Testing\Tests\Application\ForeignSysDomainTest::class,
                \In2code\In2publishCore\Testing\Tests\Fal\MissingStoragesTest::class,
                \In2code\In2publishCore\Testing\Tests\Fal\CaseSensitivityTest::class,
                \In2code\In2publishCore\Testing\Tests\Fal\IdenticalDriverTest::class,
                \In2code\In2publishCore\Testing\Tests\Fal\UniqueStorageTargetTest::class,
            ];


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
        }

        // @codingStandardsIgnoreEnd @formatter:on
    }
);
