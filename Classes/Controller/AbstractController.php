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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Utility\BackendUtility;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * class AbstractController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractController extends ActionController
{
    const BLANK_ACTION = 'blankAction';

    /**
     * @var CommonRepository
     */
    protected $commonRepository = null;

    /**
     * @var DatabaseConnection
     */
    protected $localDatabase = null;

    /**
     * @var DatabaseConnection
     */
    protected $foreignDatabase = null;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected $backendUser = null;

    /**
     * Page ID
     * UID of the selected Page in the page tree
     *
     * @var int
     */
    protected $pid = 0;

    /**
     * Creates a logger for the instantiated controller object.
     * When extending from this class and using an own constructor
     * don't forget to call this constructor method at the end
     * of your own implementation
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
        $this->pid = BackendUtility::getPageIdentifier();
        $this->backendUser = $this->getBackendUser();
    }

    /**
     * Sets the local and foreign Database Connections after checking
     * all configuration
     *
     * @return void
     */
    protected function initializeDatabaseConnections()
    {
        $this->checkConfiguration();
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignDatabase = $this->createForeignDatabaseConnection();
    }

    /**
     * Delegates the configuration check to ConfigurationUtility
     * and adds error flash messages if the check failed
     * Additionally, the ActionMethod is set to blankAction, but the Templates
     * of the original actionMethodName is used !!! (intended behaviour)
     *
     * @return void
     */
    protected function checkConfiguration()
    {
        if (!ConfigurationUtility::isConfigurationLoadedSuccessfully()) {
            $this->addFlashMessage(
                LocalizationUtility::translate(ConfigurationUtility::getLoadingState(), 'in2publish_core'),
                '',
                AbstractMessage::ERROR
            );
            $this->actionMethodName = self::BLANK_ACTION;
            $this->logger->error('Could not load Configuration');
        }
    }

    /**
     * @return NULL|DatabaseConnection
     */
    protected function createForeignDatabaseConnection()
    {
        try {
            $databaseConnection = DatabaseUtility::buildForeignDatabaseConnection();
            $message = 'See logs for more details';
        } catch (\Exception $exception) {
            $databaseConnection = null;
            $message = $exception->getMessage();
        }

        if (null === $databaseConnection) {
            $this->addFlashMessage(
                $message,
                LocalizationUtility::translate('error_not_connected', 'in2publish_core'),
                AbstractMessage::ERROR
            );
            if (static::class !== ToolsController::class) {
                $this->actionMethodName = self::BLANK_ACTION;
            }
            $this->logger->error(
                'Could not create foreign database connection. An error occured',
                ['message' => $message]
            );
        }
        return $databaseConnection;
    }

    /**
     * Decorates the original addFlashMessage Method.
     * If the controller context was not built yet it will be initialized
     *
     * Additionally creates the controller context if not done yet.
     * Do NOT call this Method before $this->initializeAction() is going to be executed
     * because it relies on $this->request and $this->response to be set
     *
     * @param string $messageBody
     * @param string $messageTitle
     * @param int $severity
     * @param bool $storeInSession
     * @return void
     */
    public function addFlashMessage(
        $messageBody,
        $messageTitle = '',
        $severity = AbstractMessage::OK,
        $storeInSession = true
    ) {
        if ($this->controllerContext === null) {
            $this->logger->debug('Prematurely building ControllerContext');
            $this->controllerContext = $this->buildControllerContext();
        }
        parent::addFlashMessage($messageBody, $messageTitle, $severity, $storeInSession);
    }

    /**
     * Checks if both local and foreign database connections
     * are available and assigns any status to the view
     *
     * @return void
     */
    protected function assignServerAndPublishingStatus()
    {
        $localDatabaseOn = $this->localDatabase !== null;
        $foreignDatabaseOn = $this->foreignDatabase !== null;
        $testStatus = GeneralUtility::makeInstance('In2code\\In2publishCore\\Service\\Environment\\EnvironmentService')
                                    ->getTestStatus();
        $this->view->assignMultiple(
            array(
                'localDatabaseConnectionAvailable' => $localDatabaseOn,
                'foreignDatabaseConnectionAvailable' => $foreignDatabaseOn,
                'publishingAvailable' => $localDatabaseOn && $foreignDatabaseOn,
                'testStatus' => empty($testStatus),
            )
        );
    }

    /**
     * Dummy Method to use when an error occurred
     * This Method must never throw an exception
     *
     * @return void
     */
    public function blankAction()
    {
        $this->assignServerAndPublishingStatus();
    }

    /**
     * Deactivate error messages in flash messages by
     * explicitly returning FALSE
     *
     * @return string
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }

    /**
     * Check if user is allowed to publish
     *
     * @throws \Exception
     */
    protected function checkUserAllowedToPublish()
    {
        $votes = array('yes' => 0, 'no' => 0);
        $votingResult = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')->dispatch(
            __CLASS__,
            __FUNCTION__,
            array($votes)
        );
        if (isset($votingResult[0])) {
            $votes = $votingResult[0];
        }
        if ($votes['no'] > $votes['yes']) {
            throw new \Exception('You are not allowed to publish', 1435306780);
        }
    }

    /**
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $this->view->assignMultiple(
            array(
                'extensionVersion' => ExtensionManagementUtility::getExtensionVersion('in2publish_core'),
                'configurationExists' => $this->actionMethodName !== self::BLANK_ACTION,
            )
        );
        if (ConfigurationUtility::isConfigurationLoadedSuccessfully()) {
            $this->view->assign('configuration', ConfigurationUtility::getConfiguration());
        }
    }

    /**
     * Initializes both database connections, sets the pid of the current
     * selected page, the current backend user and an instance of CommonRepository
     * responsible for pages handling
     *
     * @return void
     */
    public function initializeAction()
    {
        /** @var \In2code\In2publishCore\Domain\Service\ExecutionTimeService $executionTimeService */
        $executionTimeService = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Service\\ExecutionTimeService'
        );
        $executionTimeService->start();
        $this->initializeDatabaseConnections();
        $this->commonRepository = CommonRepository::getDefaultInstance();
    }

    /**
     * @return void
     */
    protected function checkTestStatus()
    {
        $testStates = GeneralUtility::makeInstance('In2code\\In2publishCore\\Service\\Environment\\EnvironmentService')
                                    ->getTestStatus();
        if (!empty($testStates)) {
            $messages = array();
            foreach ($testStates as $testState) {
                $messages[] = LocalizationUtility::translate('test_state_error.' . $testState, 'in2publish_core');
            }
            $this->addFlashMessage(
                implode('<br/>', $messages),
                LocalizationUtility::translate('test_state_error', 'in2publish_core'),
                AbstractMessage::ERROR
            );
        }
    }

    /**
     * Gets an instance of SshConnection to execute tasks on Foreign
     *
     * @return void
     */
    protected function runTasks()
    {
        $dispatcher = GeneralUtility::makeInstance(RemoteCommandDispatcher::class);
        $request = GeneralUtility::makeInstance(RemoteCommandRequest::class, 'publishtasksrunner:runtasksinqueue');
        $response = $dispatcher->dispatch($request);

        if ($response->isSuccessful()) {
            $this->logger->notice('Task execution results', ['output' => $response->getOutput()]);
        } else {
            $this->logger->error(
                'Task execution failed',
                [
                    'output' => $response->getOutput(),
                    'errors' => $response->getErrors(),
                    'exit_status' => $response->getExitStatus(),
                ]
            );
            $this->addFlashMessage(
                implode('<br/>', $response->getOutput()) . implode('<br/>', $response->getErrors()),
                LocalizationUtility::translate('publishing.tasks_failure', 'in2publish_core'),
                AbstractMessage::ERROR
            );
        }
    }

    /**
     * @param string $filterName
     * @param string $status
     * @param string $action
     */
    protected function toggleFilterStatusAndRedirect($filterName, $status, $action)
    {
        $currentStatus = $this->backendUser->getSessionData($filterName . $status);
        if (!is_bool($currentStatus)) {
            $currentStatus = false;
        }
        $this->backendUser->setAndSaveSessionData($filterName . $status, !$currentStatus);
        $this->redirect($action);
    }

    /**
     * @return BackendUserAuthentication
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
