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

use In2code\In2publishCore\Communication\RemoteProcedureCall\Letterbox;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Testing\Service\TestingService;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Tools\ToolsRegistry;
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
use function implode;
use function json_decode;
use function json_encode;
use function php_uname;
use const PHP_EOL;
use const PHP_OS;
use const PHP_VERSION;

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

    public function systemInfoAction()
    {
        $info = [
            'packages' => $this->getPackagesInfoArray(),
            'testStatus' => $this->getTests(),
            'config' => $this->getConfig(),
            'systemInformation' => $this->getSysInfo(),
        ];
        $this->view->assign('info', $info);
        $this->view->assign('json', json_encode($info));
        $this->view->assign('showDecode', Environment::getContext()->isDevelopment());
    }

    /**
     * @param string $json
     */
    public function decodeAction(string $json = '')
    {
        $this->view->assign('info', json_decode($json, true));
        $this->view->assign('infoJson', $json);
    }

    /**
     * @return array
     */
    protected function getPackagesInfoArray(): array
    {
        $listUtility = $this->objectManager->get(ListUtility::class);
        $packages = $listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $packageInfo = [];
        foreach ($packages as $package) {
            $packageInfo[$package['key']] = [
                'title' => $package['title'],
                'state' => $package['state'],
                'version' => $package['version'],
                'installed' => $package['installed'],
            ];
        }
        return $packageInfo;
    }

    protected function getSysInfo()
    {
        $databases = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach ($connectionPool->getConnectionNames() as $connectionName) {
            $databases[$connectionName] = $connectionPool->getConnectionByName($connectionName)->getServerVersion();
        }
        return [
            'TYPO3 Version' => VersionNumberUtility::getCurrentTypo3Version(),
            '$_SERVER ' => $_SERVER,
            'PHP Version' => PHP_VERSION,
            'Database Connections' => $databases,
            'Application Context' => GeneralUtility::getApplicationContext()->__toString(),
            'Composer mode' => Environment::isComposerMode(),
            'Operating System' => PHP_OS . ' ' . php_uname('r'),
        ];
    }

    /**
     * @return array
     */
    protected function getConfig(): array
    {
        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $full = $configContainer->getContextFreeConfig();
        $pers = $configContainer->get();

        $protectedValues = [
            'foreign.database.password',
            'sshConnection.privateKeyPassphrase',
        ];
        foreach ($protectedValues as $protectedValue) {
            foreach ([&$full, &$pers] as &$cfgArray) {
                $value = ArrayUtility::getValueByPath($cfgArray, $protectedValue, '.');
                if (!empty($value)) {
                    $value = 'xxxxxxxx (masked)';
                    $cfgArray = ArrayUtility::setValueByPath($cfgArray, $protectedValue, $value, '.');
                }
            }
        }
        return [
            'personal' => $pers,
            'full' => $full,
        ];
    }

    protected function getTests()
    {
        $return = [];
        $testingService = new TestingService();
        $testingResults = $testingService->runAllTests();
        foreach ($testingResults as $testClass => $testingResult) {
            $severityString = '[' . $testingResult->getSeverityLabel() . '] ';
            $message = '[' . $testingResult->getTranslatedLabel() . '] ' . $testingResult->getTranslatedMessages();

            $return[$testingResult->getSeverity()][$severityString . $testClass] = $message;
        }

        $sortedResult = [];
        foreach ([TestResult::ERROR, TestResult::WARNING, TestResult::SKIPPED, TestResult::OK] as $severity) {
            if (isset($return[$severity])) {
                $sortedResult = array_merge($sortedResult, $return[$severity]);
            }
        }
        return $sortedResult;
    }
}
