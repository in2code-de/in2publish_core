<?php

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Controller\FileController;
use In2code\In2publishCore\Controller\RecordController;
use In2code\In2publishCore\Features\ContextMenuPublishEntry\ContextMenu\PublishItemProvider;
use In2code\In2publishCore\Features\RedirectsSupport\Controller\RedirectController;
use In2code\In2publishCore\Features\WarningOnForeign\Service\HeaderWarningColorRenderer;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Testing\Tests\Adapter\AdapterSelectionTest;
use In2code\In2publishCore\Testing\Tests\Adapter\RemoteAdapterTest;
use In2code\In2publishCore\Testing\Tests\Adapter\TransmissionAdapterTest;
use In2code\In2publishCore\Testing\Tests\Application\ForeignDatabaseConfigTest;
use In2code\In2publishCore\Testing\Tests\Application\ForeignDomainTest;
use In2code\In2publishCore\Testing\Tests\Application\ForeignInstanceTest;
use In2code\In2publishCore\Testing\Tests\Application\LocalDomainTest;
use In2code\In2publishCore\Testing\Tests\Application\LocalInstanceTest;
use In2code\In2publishCore\Testing\Tests\Application\SiteConfigurationTest;
use In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationFormatTest;
use In2code\In2publishCore\Testing\Tests\Configuration\ConfigurationMigrationTest;
use In2code\In2publishCore\Testing\Tests\Configuration\ForeignConfigurationFormatTest;
use In2code\In2publishCore\Testing\Tests\Database\DatabaseDifferencesTest;
use In2code\In2publishCore\Testing\Tests\Database\ForeignDatabaseTest;
use In2code\In2publishCore\Testing\Tests\Database\LocalDatabaseTest;
use In2code\In2publishCore\Testing\Tests\Database\TableGarbageCollectorTest;
use In2code\In2publishCore\Testing\Tests\Fal\CaseSensitivityTest;
use In2code\In2publishCore\Testing\Tests\Fal\DefaultStorageIsConfiguredTest;
use In2code\In2publishCore\Testing\Tests\Fal\IdenticalDriverTest;
use In2code\In2publishCore\Testing\Tests\Fal\MissingStoragesTest;
use In2code\In2publishCore\Testing\Tests\Fal\UniqueStorageTargetTest;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    /***************************************************** Guards *****************************************************/
    if (!defined('TYPO3')) {
        die('Access denied.');
    }
    if (!class_exists(ContextService::class)) {
        // Early return when installing per ZIP: autoload is not yet generated
        return;
    }
    if (!(TYPO3_REQUESTTYPE & (TYPO3_REQUESTTYPE_BE | TYPO3_REQUESTTYPE_CLI))) {
        // Do nothing when not in any of the desirable modes.
        return;
    }

    /**************************************************** Instances ***************************************************/
    $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
    $contextService = GeneralUtility::makeInstance(ContextService::class);
    $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
    $isForeign = $contextService->isForeign();

    /******************************************* Colorize the BE on Foreign *******************************************/
    if ($isForeign && $configContainer->get('features.warningOnForeign.colorizeHeader.enable')) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'][1582191172] = HeaderWarningColorRenderer::class . '->render';
    }

    /************************************************* END ON FOREIGN *************************************************/
    if ($isForeign) {
        return;
    }

    /******************************************** Register Backend Modules ********************************************/
    if ($configContainer->get('module.m1')) {
        ExtensionUtility::registerModule(
            'in2publish_core',
            'web',
            'm1',
            '',
            [
                RecordController::class => 'index,detail,publishRecord,toggleFilterStatus',
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:in2publish_core/Resources/Public/Icons/Overview.svg',
                'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod1.xlf',
            ],
        );
    }
    if ($configContainer->get('module.m3')) {
        ExtensionUtility::registerModule(
            'in2publish_core',
            'file',
            'm3',
            '',
            [
                FileController::class => 'index,publishFolder,publishFile,toggleFilterStatus',
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:in2publish_core/Resources/Public/Icons/File.svg',
                'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod3.xlf',
            ],
        );
    }

    /******************************************* Context Menu Publish Entry *******************************************/
    if ($configContainer->get('features.contextMenuPublishEntry.enable')) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1595598780] = PublishItemProvider::class;
        $iconRegistry->registerIcon(
            'tx_in2publishcore_contextmenupublishentry_publish',
            SvgIconProvider::class,
            ['source' => 'EXT:in2publish_core/Resources/Public/Icons/Publish.svg'],
        );
    }

    /*********************************************** Tests Registration ***********************************************/
    $GLOBALS['in2publish_core']['tests'][] = AdapterSelectionTest::class;
    $GLOBALS['in2publish_core']['tests'][] = ConfigurationFormatTest::class;
    $GLOBALS['in2publish_core']['tests'][] = ConfigurationMigrationTest::class;
    $GLOBALS['in2publish_core']['tests'][] = LocalDatabaseTest::class;
    $GLOBALS['in2publish_core']['tests'][] = ForeignDatabaseTest::class;
    $GLOBALS['in2publish_core']['tests'][] = DatabaseDifferencesTest::class;
    $GLOBALS['in2publish_core']['tests'][] = RemoteAdapterTest::class;
    $GLOBALS['in2publish_core']['tests'][] = TransmissionAdapterTest::class;
    $GLOBALS['in2publish_core']['tests'][] = LocalInstanceTest::class;
    $GLOBALS['in2publish_core']['tests'][] = LocalDomainTest::class;
    $GLOBALS['in2publish_core']['tests'][] = ForeignDatabaseConfigTest::class;
    $GLOBALS['in2publish_core']['tests'][] = ForeignInstanceTest::class;
    $GLOBALS['in2publish_core']['tests'][] = ForeignDomainTest::class;
    $GLOBALS['in2publish_core']['tests'][] = CaseSensitivityTest::class;
    $GLOBALS['in2publish_core']['tests'][] = DefaultStorageIsConfiguredTest::class;
    $GLOBALS['in2publish_core']['tests'][] = IdenticalDriverTest::class;
    $GLOBALS['in2publish_core']['tests'][] = MissingStoragesTest::class;
    $GLOBALS['in2publish_core']['tests'][] = UniqueStorageTargetTest::class;
    $GLOBALS['in2publish_core']['tests'][] = ForeignConfigurationFormatTest::class;
    $GLOBALS['in2publish_core']['tests'][] = SiteConfigurationTest::class;
    $GLOBALS['in2publish_core']['tests'][] = TableGarbageCollectorTest::class;

    /************************************************ Redirect Support ************************************************/
    if (
        $configContainer->get('features.redirectsSupport.enable')
        && ExtensionManagementUtility::isLoaded('redirects')
    ) {
        ExtensionUtility::registerModule(
            'in2publish_core',
            'site',
            'm5',
            'after:redirects',
            [
                RedirectController::class => 'list,publish,selectSite',
            ],
            [
                'access' => 'user,group',
                'icon' => 'EXT:in2publish_core/Resources/Public/Icons/Redirect.svg',
                'labels' => 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod5.xlf',
            ],
        );
    }
})();
