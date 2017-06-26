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

use In2code\In2publishCore\Domain\Driver\Rpc\Letterbox;
use In2code\In2publishCore\Domain\Service\TcaService;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Testing\Service\TestingService;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * The ToolsController is the controller of the Backend Module "Publish Tools" "m3"
 */
class ToolsController extends AbstractController
{
    /**
     * @var \In2code\In2publishCore\Domain\Repository\LogEntryRepository
     * @inject
     */
    protected $logEntryRepository = null;

    /**
     * @var array
     */
    protected $tests = array();

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
     * @return void
     */
    public function indexAction()
    {
        $this->checkTestStatus();
    }

    /**
     * This action is used only for JavaScript Unit Tests
     *
     * @return void
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

        GeneralUtility::makeInstance(EnvironmentService::class)
                      ->setTestResult($success);

        $this->view->assign('testingResults', $testingResults);
    }

    /**
     * Finds all logs by filters and assigns them to the view
     *
     * @param array $filter
     * @param int $pageNumber
     * @return void
     */
    public function showLogsAction(array $filter = array(), $pageNumber = 1)
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
            array(
                'limits' => array(25 => 25, 50 => 50, 100 => 100, 150 => 150, 250 => 250),
                'limit' => $filter['limit'],
                'logLevels' => $logLevels,
                'level' => $filter['level'],
            )
        );
        $numberOfPages = ceil($this->logEntryRepository->countFiltered() / $filter['limit']);
        $pageNumbers = array();
        for ($i = 1; $i <= $numberOfPages; $i++) {
            $pageNumbers[] = $i;
        }
        $this->view->assignMultiple(
            array(
                'logsCount' => $this->logEntryRepository->countAll(),
                'numberOfPages' => $numberOfPages,
                'pageNumbers' => $pageNumbers,
                'currentPage' => $pageNumber,
            )
        );
        $this->view->assign('logEntries', $this->logEntryRepository->getFiltered());
        $this->view->assign('logConfigurations', ConfigurationUtility::getConfiguration('log'));
    }

    /**
     * Show configuration
     *
     * @return void
     */
    public function configurationAction()
    {
        $this->view->assign('configuration', ConfigurationUtility::getPublicConfiguration());
    }

    /**
     * applies the selected filters to the repository
     *
     * @param array $filters
     * @return void
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
     * @return void
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
        $this->view->assign('incompatibleTca', TcaService::getIncompatibleTca());
        $this->view->assign('compatibleTca', TcaService::getCompatibleTca());
        $this->view->assign('controls', TcaService::getControls());
    }

    /**
     *
     */
    public function clearTcaCachesAction()
    {
        TcaService::getInstance()->flushCaches();
        $this->redirect('index');
    }

    /**
     *
     */
    public function flushRegistryAction()
    {
        GeneralUtility::makeInstance(Registry::class)->removeAllByNamespace('tx_in2publishcore');
        $this->addFlashMessage(LocalizationUtility::translate('module.m4.registry_flushed', 'in2publish_core'));
        $this->redirect('index');
    }

    /**
     *
     */
    public function flushEnvelopesAction()
    {
        $letterbox = GeneralUtility::makeInstance(Letterbox::class);
        $letterbox->removeAnsweredEnvelopes();
        $this->redirect('index');
    }
}
