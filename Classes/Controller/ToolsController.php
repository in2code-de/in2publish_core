<?php
namespace In2code\In2publishCore\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Communication\RemoteProcedureCall\Letterbox;
use In2code\In2publishCore\Domain\Repository\LogEntryRepository;
use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Testing\Service\TestingService;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Tools\ToolsRegistry;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * The ToolsController is the controller of the Backend Module "Publish Tools" "m3"
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ToolsController extends ActionController
{
    /**
     * @var LogEntryRepository
     */
    protected $logEntryRepository = null;

    /**
     * @var array
     */
    protected $tests = [];

    /**
     * ToolsController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->logEntryRepository = GeneralUtility::makeInstance(LogEntryRepository::class);
    }

    /**
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $letterbox = GeneralUtility::makeInstance(Letterbox::class);
        $this->view->assign('canFlushEnvelopes', $letterbox->hasUnAnsweredEnvelopes());
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
                implode('<br/>', $messages),
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
     * Finds all logs by filters and assigns them to the view
     *
     * @param array $filter
     * @param int $pageNumber
     *
     * @throws In2publishCoreException
     */
    public function showLogsAction(array $filter = [], $pageNumber = 1)
    {
        if (empty($filter['limit'])) {
            $filter['limit'] = 25;
        }
        $logLevels = $this->logEntryRepository->getLogLevels();
        if (empty($filter['level'])) {
            $filter['level'] = end($logLevels);
        }
        $filter['offset'] = ($pageNumber - 1) * $filter['limit'];
        $this->setFilters($filter);
        reset($logLevels);
        $this->view->assign(
            'filter',
            [
                'limits' => [25 => 25, 50 => 50, 100 => 100, 150 => 150, 250 => 250],
                'limit' => $filter['limit'],
                'logLevels' => $logLevels,
                'level' => $filter['level'],
            ]
        );
        $numberOfPages = ceil($this->logEntryRepository->countFiltered() / $filter['limit']);
        $pageNumbers = [];
        for ($i = 1; $i <= $numberOfPages; $i++) {
            $pageNumbers[] = $i;
        }
        $this->view->assignMultiple(
            [
                'logsCount' => $this->logEntryRepository->countAll(),
                'numberOfPages' => $numberOfPages,
                'pageNumbers' => $pageNumbers,
                'currentPage' => $pageNumber,
            ]
        );
        $this->view->assign('logEntries', $this->logEntryRepository->getFiltered());
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
     * applies the selected filters to the repository
     *
     * @param array $filters
     *
     * @throws In2publishCoreException
     */
    protected function setFilters(array $filters)
    {
        $this->logEntryRepository->setLimit($filters['limit']);
        $this->logEntryRepository->setOffset($filters['offset']);
        $filtersToSet = array_intersect_key($filters, array_flip($this->logEntryRepository->getPropertyNames()));
        foreach ($filtersToSet as $propertyName => $propertyValue) {
            $this->logEntryRepository->setFilter($propertyName, $propertyValue);
        }
    }

    /**
     * deletes ALL logs from the database
     *
     * @throws StopActionException
     */
    public function flushLogsAction()
    {
        $this->logEntryRepository->flush();
        $this->forward('showLogs');
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
        try {
            $this->redirect('index');
        } catch (UnsupportedRequestTypeException $e) {
        }
    }
}
