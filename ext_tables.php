<?php

/** @noinspection PhpFullyQualifiedNameUsageInspection */

(static function () {
    /***************************************************** Guards *****************************************************/
    if (!defined('TYPO3')) {
        die('Access denied.');
    }
    if (!class_exists(\In2code\In2publishCore\Service\Context\ContextService::class)) {
        // Early return when installing per ZIP: autoload is not yet generated
        return;
    }
    if (!(TYPO3_REQUESTTYPE & (TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI))) {
        // Do nothing when not in any of the desirable modes.
        return;
    }

    /**************************************************** Instances ***************************************************/
    $configContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Config\ConfigContainer::class
    );
    $contextService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \In2code\In2publishCore\Service\Context\ContextService::class
    );
    $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Page\PageRenderer::class
    );
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Imaging\IconRegistry::class
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
            'in2publish_core',
            'web',
            'm1',
            '',
            [
                \In2code\In2publishCore\Controller\RecordController::class => 'index,detail,publishRecord,toggleFilterStatus',
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
            'in2publish_core',
            'file',
            'm3',
            '',
            [
                \In2code\In2publishCore\Controller\FileController::class => 'index,publishFolder,publishFile,toggleFilterStatus',
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:in2publish_core/Resources/Public/Icons/File.svg',
                'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod3.xlf',
            ]
        );
    }


    /******************************************* Context Menu Publish Entry *******************************************/
    if ($configContainer->get('features.contextMenuPublishEntry.enable')) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1595598780] = \In2code\In2publishCore\Features\ContextMenuPublishEntry\ContextMenu\PublishItemProvider::class;
        $iconRegistry->registerIcon(
            'tx_in2publishcore_contextmenupublishentry_publish',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:in2publish_core/Resources/Public/Icons/Publish.svg']
        );
    }

    /*********************************************** Tests Registration ***********************************************/
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Adapter\AdapterSelectionTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationFormatTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationValuesTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationMigrationTest::class;
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
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Performance\RceInitializationPerformanceTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Performance\ForeignDbInitializationPerformanceTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Performance\DiskSpeedPerformanceTest::class;
    $GLOBALS['in2publish_core']['tests'][] = \In2code\In2publishCore\Testing\Tests\Application\SiteConfigurationTest::class;

    /************************************************ Redirect Support ************************************************/
    if (
        $configContainer->get('features.redirectsSupport.enable')
        && \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('redirects')
    ) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'in2publish_core',
            'site',
            'm5',
            'after:redirects',
            [
                \In2code\In2publishCore\Features\RedirectsSupport\Controller\RedirectController::class => 'list,publish,selectSite',
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:in2publish_core/Resources/Public/Icons/Redirect.svg',
                'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod5.xlf',
            ]
        );
    }
})();
