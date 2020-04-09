<?php
(function () {
    /***************************************************** Guards *****************************************************/
    if (!defined('TYPO3_REQUESTTYPE')) {
        die('Access denied.');
    } elseif (!(TYPO3_REQUESTTYPE & (TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI))) {
        return;
    }
    if (!class_exists(\In2code\In2publishCore\Service\Context\ContextService::class)) {
        // Early return when installing per ZIP: autoload is not yet generated
        return;
    }

    /*********************************************** Settings/Instances ***********************************************/
    $contextService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Service\Context\ContextService::class
    );
    $configContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Config\ConfigContainer::class
    );
    $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Page\PageRenderer::class
    );
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class
    );

    /***************************************** Add JS and CSS for the Backend *****************************************/
    $pageRenderer->loadRequireJsModule('TYPO3/CMS/In2publishCore/BackendModule');
    $pageRenderer->addCssFile(
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath(
            'in2publish_core',
            'Resources/Public/Css/Modules.css'
        ),
        'stylesheet',
        'all',
        '',
        false
    );

    if ($contextService->isForeign()) {
        if ($configContainer->get('features.warningOnForeign.colorizeHeader.enable')) {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][1582191172] = \In2code\In2publishCore\Features\WarningOnForeign\Service\HeaderWarningColorRenderer::class . '->render';
        }
    }
    /************************************************* END ON FOREIGN *************************************************/
    if ($contextService->isForeign()) {
        return;
    }

    /******************************************** Register Backend Modules ********************************************/
    if ($configContainer->get('module.m1')) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'In2code.In2publishCore',
            'web',
            'm1',
            '',
            [
                'Record' => 'index,detail,publishRecord,toggleFilterStatusAndRedirectToIndex',
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
    if ($configContainer->get('module.m4')) {
        $toolsRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \In2code\In2publishCore\Tools\ToolsRegistry::class
        );

        /*********************************************** Register Tools ***********************************************/
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
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('logs')) {
            $toolsRegistry->addTool(
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.logs',
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.logs.description',
                'Log',
                'filter,delete,deleteAlike'
            );
        }
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
        $toolsRegistry->addTool(
            'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_envelopes',
            'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.flush_envelopes.description',
            'Tools',
            'flushEnvelopes'
        );
        $toolsRegistry->addTool(
            'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.system_info',
            'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:moduleselector.system_info.description',
            'Tools',
            'sysInfoIndex,sysInfoShow,sysInfoDecode,sysInfoDownload,sysInfoUpload'
        );
    }

    /********************************************** Anomaly Registration **********************************************/
    $signalSlotDispatcher->connect(
        \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
        'publishRecordRecursiveBeforePublishing',
        \In2code\In2publishCore\Features\CacheInvalidation\Domain\Anomaly\CacheInvalidator::class,
        'registerClearCacheTasks',
        false
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
        'publishRecordRecursiveAfterPublishing',
        \In2code\In2publishCore\Features\SysLogPublisher\Domain\Anomaly\SysLogPublisher::class,
        'publishSysLog',
        false
    );
    $signalSlotDispatcher->connect(
        \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
        'publishRecordRecursiveAfterPublishing',
        \In2code\In2publishCore\Features\RefIndexUpdate\Domain\Anomaly\RefIndexUpdater::class,
        'registerRefIndexUpdate',
        false
    );
    $signalSlotDispatcher->connect(
        \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
        'publishRecordRecursiveEnd',
        \In2code\In2publishCore\Features\RefIndexUpdate\Domain\Anomaly\RefIndexUpdater::class,
        'writeRefIndexUpdateTask',
        false
    );
    $signalSlotDispatcher->connect(
        \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
        'publishRecordRecursiveEnd',
        \In2code\In2publishCore\Features\CacheInvalidation\Domain\Anomaly\CacheInvalidator::class,
        'writeClearCacheTask',
        false
    );
    if ($configContainer->get('factory.fal.reserveSysFileUids')) {
        $indexPostProcessor = \In2code\In2publishCore\Domain\PostProcessing\FileIndexPostProcessor::class;
    } else {
        $indexPostProcessor = \In2code\In2publishCore\Domain\PostProcessing\FalIndexPostProcessor::class;
    }
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
    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
        $signalSlotDispatcher->connect(
            \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
            'publishRecordRecursiveBeforePublishing',
            \In2code\In2publishCore\Features\NewsSupport\Domain\Anomaly\NewsCacheInvalidator::class,
            'registerClearCacheTasks',
            false
        );
        $signalSlotDispatcher->connect(
            \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
            'publishRecordRecursiveEnd',
            \In2code\In2publishCore\Features\NewsSupport\Domain\Anomaly\NewsCacheInvalidator::class,
            'writeClearCacheTask',
            false
        );
    }

    /*********************************************** Tests Registration ***********************************************/
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Adapter\AdapterSelectionTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationFormatTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationValuesTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Database\LocalDatabaseTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Database\ForeignDatabaseTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Database\DatabaseDifferencesTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Adapter\RemoteAdapterTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Adapter\TransmissionAdapterTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Application\LocalInstanceTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Application\LocalDomainTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Application\ForeignDatabaseConfigTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Application\ForeignInstanceTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Application\ForeignDomainTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Fal\CaseSensitivityTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Fal\DefaultStorageIsConfiguredTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Fal\IdenticalDriverTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Fal\MissingStoragesTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Fal\UniqueStorageTargetTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Configuration\ForeignConfigurationFormatTest::class;
})();
