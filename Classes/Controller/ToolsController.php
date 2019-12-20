<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Controller;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use Doctrine\DBAL\DBALException;
use In2code\In2publishCore\Communication\RemoteProcedureCall\Letterbox;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Testing\Service\TestingService;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Tools\ToolsRegistry;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Throwable;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use function array_merge;
use function class_exists;
use function defined;
use function file_get_contents;
use function flush;
use function gettype;
use function gmdate;
use function header;
use function implode;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;
use function ob_clean;
use function ob_end_clean;
use function ob_get_level;
use function php_uname;
use function sprintf;
use function strftime;
use function strlen;
use function substr;
use function time;
use function unserialize;
use const PHP_EOL;
use const PHP_OS;
use const PHP_VERSION;
use const TYPO3_COMPOSER_MODE;

/**
 * The ToolsController is the controller of the Backend Module "Publish Tools" "m3"
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ToolsController extends ActionController
{
    const LOG_INIT_DB_ERROR = 'Error while initialization. The Database is not correctly configured';

    /**
     * @var array
     */
    protected $tests = [];

    /**
     * @param ViewInterface $view
     *
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $letterbox = GeneralUtility::makeInstance(Letterbox::class);
        try {
            $this->view->assign('canFlushEnvelopes', $letterbox->hasUnAnsweredEnvelopes());
        } catch (Throwable $throwable) {
            $this->logger->error(self::LOG_INIT_DB_ERROR, ['exception' => $throwable]);
        }
    }

    /**
     *
     */
    public function indexAction()
    {
        $testStates = GeneralUtility::makeInstance(EnvironmentService::class)->getTestStatus();

        $messages = [];
        foreach ($testStates as $testState) {
            $messages[] = LocalizationUtility::translate('test_state_error.' . $testState, 'in2publish_core');
        }
        if (!empty($messages)) {
            $this->addFlashMessage(
                implode(PHP_EOL, $messages),
                LocalizationUtility::translate('test_state_error', 'in2publish_core'),
                AbstractMessage::ERROR
            );
        }

        $this->view->assign('tools', GeneralUtility::makeInstance(ToolsRegistry::class)->getTools());
    }

    /**
     * @throws In2publishCoreException
     */
    public function testAction()
    {
        $testingService = new TestingService();
        $testingResults = $testingService->runAllTests();

        $success = true;

        foreach ($testingResults as $testingResult) {
            if ($testingResult->getSeverity() === TestResult::ERROR) {
                $success = false;
                break;
            }
        }

        GeneralUtility::makeInstance(EnvironmentService::class)->setTestResult($success);

        $this->view->assign('testingResults', $testingResults);
    }

    /**
     * Show configuration
     *
     * @return void
     */
    public function configurationAction()
    {
        $this->view->assign('globalConfig', $this->configContainer->getContextFreeConfig());
        $this->view->assign('personalConfig', $this->configContainer->get());
    }

    /**
     * @return void
     */
    public function tcaAction()
    {
        $this->view->assign('incompatibleTca', TcaProcessingService::getIncompatibleTca());
        $this->view->assign('compatibleTca', TcaProcessingService::getCompatibleTca());
        $this->view->assign('controls', TcaProcessingService::getControls());
    }

    /**
     * @throws StopActionException
     */
    public function clearTcaCachesAction()
    {
        TcaProcessingService::getInstance()->flushCaches();
        try {
            $this->redirect('index');
        } catch (UnsupportedRequestTypeException $e) {
        }
    }

    /**
     * @throws StopActionException
     */
    public function flushRegistryAction()
    {
        GeneralUtility::makeInstance(Registry::class)->removeAllByNamespace('tx_in2publishcore');
        $this->addFlashMessage(LocalizationUtility::translate('module.m4.registry_flushed', 'in2publish_core'));
        try {
            $this->redirect('index');
        } catch (UnsupportedRequestTypeException $e) {
        }
    }

    /**
     * @throws StopActionException
     */
    public function flushEnvelopesAction()
    {
        GeneralUtility::makeInstance(Letterbox::class)->removeAnsweredEnvelopes();
        $this->addFlashMessage(
            LocalizationUtility::translate(
                'module.m4.superfluous_envelopes_flushed',
                'in2publish_core'
            )
        );
        try {
            $this->redirect('index');
        } catch (UnsupportedRequestTypeException $e) {
        }
    }

    public function sysInfoIndexAction()
    {
    }

    /**
     *
     */
    public function sysInfoShowAction()
    {
        $info = $this->getFullInfo();
        $this->view->assign('info', $info);
        $this->view->assign('infoJson', json_encode($info));
    }

    /**
     * @param string $json
     */
    public function sysInfoDecodeAction(string $json = '')
    {
        if (!empty($json)) {
            $info = json_decode($json, true);
            if (is_array($info)) {
                $this->view->assign('info', $info);
            } else {
                $args = [json_last_error(), json_last_error_msg()];
                $this->addFlashMessage(
                    LocalizationUtility::translate('system_info.decode.json_error.details', 'in2publish_core', $args),
                    LocalizationUtility::translate('system_info.decode.json_error', 'in2publish_core'),
                    AbstractMessage::ERROR
                );
            }
        }
        $this->view->assign('infoJson', $json);
    }

    /**
     *
     */
    public function sysInfoDownloadAction()
    {
        $info = $this->getFullInfo();
        $json = json_encode($info);

        $downloadName = 'cp_sysinfo_' . time() . '.json';
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Type: text/json');
        header('Content-Length: ' . strlen($json));
        header("Cache-Control: ''");
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT', true, 200);
        ob_clean();
        flush();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo $json;
        die;
    }

    public function sysInfoUploadAction()
    {
        /** @var array $file */
        $file = $this->request->getArgument('jsonFile');
        $content = file_get_contents($file['tmp_name']);
        $this->forward('sysInfoDecode', null, null, ['json' => $content]);
    }

    /**
     * @return array
     * @throws In2publishCoreException
     * @throws DBALException
     */
    protected function getFullInfo(): array
    {
        $listUtility = $this->objectManager->get(ListUtility::class);
        $packages = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $extensions = [];
        foreach ($packages as $package) {
            $extensions[$package['key']] = [
                'title' => $package['title'],
                'state' => $package['state'],
                'version' => $package['version'],
                'installed' => $package['installed'],
            ];
        }

        $return = [];
        $testingService = new TestingService();
        $testingResults = $testingService->runAllTests();
        foreach ($testingResults as $testClass => $testingResult) {
            $severityString = '[' . $testingResult->getSeverityLabel() . '] ';
            $message = '[' . $testingResult->getTranslatedLabel() . '] ' . $testingResult->getTranslatedMessages();

            $return[$testingResult->getSeverity()][$severityString . $testClass] = $message;
        }

        $tests = [];
        foreach ([TestResult::ERROR, TestResult::WARNING, TestResult::SKIPPED, TestResult::OK] as $severity) {
            if (isset($return[$severity])) {
                $tests = array_merge($tests, $return[$severity]);
            }
        }

        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $full = $configContainer->getContextFreeConfig();
        $pers = $configContainer->get();

        $protectedValues = [
            'foreign.database.password',
            'sshConnection.privateKeyPassphrase',
        ];
        foreach ($protectedValues as $protectedValue) {
            foreach ([&$full, &$pers] as &$cfgArray) {
                try {
                    $value = ArrayUtility::getValueByPath($cfgArray, $protectedValue, '.');
                    if (!empty($value)) {
                        $value = 'xxxxxxxx (masked)';
                        $cfgArray = ArrayUtility::setValueByPath($cfgArray, $protectedValue, $value, '.');
                    }
                } catch (Throwable $e) {
                }
            }
        }

        $extConf = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'] as $extKey => $extConfs) {
            if (is_string($extConfs)) {
                $extConf[$extKey] = unserialize($extConfs);
            } else {
                $extConf[$extKey] = 'NOT UNSERIALIZEABLE: ' . gettype($extConfs);
            }
        }

        $databases = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach ($connectionPool->getConnectionNames() as $connectionName) {
            $databases[$connectionName] = $connectionPool->getConnectionByName($connectionName)->getServerVersion();
        }

        $composerMode = class_exists(Environment::class)
            ? Environment::isComposerMode()
            : defined('TYPO3_COMPOSER_MODE') && true === TYPO3_COMPOSER_MODE;

        $logQueryBuilder = $connectionPool->getQueryBuilderForTable('tx_in2publishcore_log');
        $logs = $logQueryBuilder->select('*')
                                ->from('tx_in2publishcore_log')
                                ->where($logQueryBuilder->expr()->lte('level', 4))
                                ->setMaxResults(500)
                                ->orderBy('uid', 'DESC')
                                ->execute()
                                ->fetchAll();

        $logsFormatted = [];
        foreach ($logs as $log) {
            $message = sprintf(
                '[%s] [lvl:%d] @%s "%s"',
                $log['component'],
                $log['level'],
                strftime('%F %T', (int)$log['time_micro']),
                $log['message']
            );
            $logData = $log['data'];
            $logDataJson = substr($logData, 2);
            $logsFormatted[$message] = json_decode($logDataJson, true);
        }

        $schema = [];
        foreach (['local', 'foreign'] as $side) {
            $schemaManager = DatabaseUtility::buildDatabaseConnectionForSide($side)->getSchemaManager();
            foreach ($schemaManager->listTables() as $table) {
                $schema[$side][$table->getName()]['options'] = $table->getOptions();
                foreach ($table->getColumns() as $column) {
                    $schema[$side][$table->getName()]['columns'][$column->getName()] = $column->toArray();
                }
                foreach ($table->getIndexes() as $index) {
                    $schema[$side][$table->getName()]['indexes'][$index->getName()] = [
                        'columns' => $index->getColumns(),
                        'isPrimary' => $index->isPrimary(),
                        'isSimple' => $index->isSimpleIndex(),
                        'isUnique' => $index->isUnique(),
                        'isQuoted' => $index->isQuoted(),
                        'options' => $index->getOptions(),
                        'flags' => $index->getFlags(),
                    ];
                }
                foreach ($table->getForeignKeys() as $foreignKey) {
                    $schema[$side][$table->getName()]['fk'][$foreignKey->getName()] = [
                        'isQuoted' => $foreignKey->isQuoted(),
                        'options' => $foreignKey->getOptions(),
                    ];
                }
            }
        }

        return [
            'TYPO3 Version' => VersionNumberUtility::getCurrentTypo3Version(),
            'PHP Version' => PHP_VERSION,
            'Database Version' => $databases,
            'Application Context' => GeneralUtility::getApplicationContext()->__toString(),
            'Composer mode' => $composerMode,
            'Operating System' => PHP_OS . ' ' . php_uname('r'),
            'extensions' => $extensions,
            'extConf' => $extConf,
            'tests' => $tests,
            'config' => $full,
            '$_SERVER ' => $_SERVER,
            'compatible TCA' => TcaProcessingService::getCompatibleTca(),
            'incompatible TCA' => TcaProcessingService::getIncompatibleTca(),
            'logs' => $logsFormatted,
            'personal config' => $pers,
            'TCA' => $GLOBALS['TCA'],
            'schema' => $schema,
        ];
    }
}
