<?php

use In2code\In2publishCore\Component\Core\Record\Model\Extension\RecordExtensionTrait;
use In2code\In2publishCore\Controller\FrontendController;
use In2code\In2publishCore\Log\Processor\BackendUserProcessor;
use In2code\In2publishCore\Log\Processor\PublishingFailureCollector;
use In2code\In2publishCore\Middleware\BackendRouteInitialization;
use In2code\In2publishCore\Service\Context\ContextService;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\DatabaseWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

(static function (): void {
    /***************************************************** Guards *****************************************************/
    if (!defined('TYPO3')) {
        die('Access denied.');
    }
    if (!class_exists(ContextService::class)) {
        // Early return when installing per ZIP: autoload is not yet generated
        return;
    }

    /************************************************* Patching TYPO3 *************************************************/
    // Issue: https://forge.typo3.org/issues/95962
    // Patch: https://review.typo3.org/c/Packages/TYPO3.CMS/+/72160
    $typo3Version = new Typo3Version();
    if (version_compare($typo3Version->getVersion(), '12', '<')) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Middleware\BackendRouteInitialization::class] = [
            'className' => BackendRouteInitialization::class,
        ];
    }

    /************************************************ Record Extension ************************************************/
    $file = Environment::getVarPath() . '/cache/code/content_publisher/record_extension_trait.php';
    if (file_exists($file)) {
        // Initialize the variable to be able to use it in a reference
        $autoloadFn = null;
        /**
         * This is a one-time autoloader that loads the compiled RecordExtensionTrait instead of the original.
         * To keep the auto-loading overhead low, the function unregisters itself,
         * so it will not interfere with further class loading.
         */
        $autoloadFn = static function (string $class) use (&$autoloadFn, $file): void {
            if (RecordExtensionTrait::class === $class) {
                spl_autoload_unregister($autoloadFn);
                unset($autoloadFn);
                include $file;
            }
        };
        spl_autoload_register($autoloadFn, true, true);
    }

    /*********************************************** Settings/Instances ***********************************************/
    $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('in2publish_core');

    /************************************************** Init Caching **************************************************/
    if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['in2publish_core'] = [];
    }

    /************************************************** Init Logging **************************************************/
    $logLevel = $extConf['logLevel'];
    $logLevel = LogLevel::getInternalName((int)$logLevel);
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['In2code']['In2publishCore'] = [
        'writerConfiguration' => [
            $logLevel => [
                DatabaseWriter::class => [
                    'logTable' => 'tx_in2publishcore_log',
                ],
            ],
        ],
        'processorConfiguration' => [
            $logLevel => [
                BackendUserProcessor::class => [],
                PublishingFailureCollector::class => [],
            ],
        ],
    ];

    /******************************************** Configure Compare Plugin ********************************************/
    ExtensionUtility::configurePlugin(
        'in2publish_core',
        'Pi1',
        [FrontendController::class => 'preview'],
        [FrontendController::class => 'preview'],
    );

    /******************************************** Configure Garbage Collector  ****************************************/
    // Register table tx_in2publishcore_running_request  in table garbage collector
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][TableGarbageCollectionTask::class]['options']['tables']['tx_in2publishcore_running_request'] = [
        'dateField' => 'timestamp_begin',
        'expirePeriod' => 1,
    ];
})();
