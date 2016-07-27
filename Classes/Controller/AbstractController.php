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

use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Security\SshConnection;
use In2code\In2publishCore\Utility\BackendUserUtility;
use In2code\In2publishCore\Utility\BackendUtility;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use In2code\In2publishCore\Utility\EnvironmentUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * class AbstractController
 *
 * @package in2publish
 * @license http://www.gnu.org/licenses/lgpl.html
 *          GNU Lesser General Public License, version 3 or later
 */
class AbstractController extends ActionController
{
    const BLANK_ACTION = 'blankAction';

    /**
     * @var CommonRepository
     */
    protected $commonRepository = null;

    /**
     * @var \In2code\In2publishCore\Domain\Repository\PageRepository
     * @inject
     */
    protected $pageRepositoy = null;

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
     * Create the controller context. Do NOT call this Method
     * before $this->initializeAction() is going to be executed
     * because it relies on $this->request and $this->response to be set
     *
     * @return void
     */
    protected function buildControllerContextIfNecessary()
    {
        if ($this->controllerContext === null) {
            $this->logger->debug('Building ControllerContext');
            $this->controllerContext = $this->buildControllerContext();
        }
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
        } else {
            $this->logger->debug('Configuration loaded successfully');
        }
    }

    /**
     * @return NULL|DatabaseConnection
     */
    protected function createForeignDatabaseConnection()
    {
        try {
            $databaseConnection = DatabaseUtility::buildForeignDatabaseConnection();
            $this->logger->debug('Successfully established foreign database connection');
            return $databaseConnection;
        } catch (\Exception $e) {
            $this->addFlashMessage(
                LocalizationUtility::translate('error_not_connected', 'in2publish_core'),
                '',
                AbstractMessage::ERROR
            );
            $this->actionMethodName = self::BLANK_ACTION;
            $this->logger->error('Could not create foreign database connection');
        }
        return null;
    }

    /**
     * Decorates the original addFlashMessage Method.
     * If the controller context was not built yet it will be initialized
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
        $this->buildControllerContextIfNecessary();
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
        $testStatus = EnvironmentUtility::getTestStatus();
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
        $this->logger->debug('Called ' . __FUNCTION__);
        return false;
    }

    /**
     * Check if user is allowed to publish
     *
     * @throws \Exception
     * @return void
     */
    protected function checkUserAllowedToPublish()
    {
        // TODO: API for VGV
        return true;
    }

    /**
     * Creates a logger for the instantiated controller object.
     * When extending from this class and using an own constructor
     * don't forget to call this constructor method at the end
     * of your onw implementation
     *
     * @return AbstractController
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
    }

    /**
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $this->view->assign('extensionVersion', ExtensionManagementUtility::getExtensionVersion('in2publish_core'));
        $this->view->assign('configurationExists', $this->actionMethodName !== self::BLANK_ACTION);
    }

    /**
     * Initializes both Database connections, sets the pid of the current
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
        $pid = BackendUtility::getPageIdentifier();
        if ($pid !== null) {
            $this->pid = $pid;
        }
        $this->backendUser = BackendUserUtility::getBackendUser();
        $this->commonRepository = CommonRepository::getDefaultInstance();
    }

    /**
     * @return void
     */
    protected function checkTestStatus()
    {
        $testStates = EnvironmentUtility::getTestStatus();
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
     * Gets in instance of SSH Connection
     * to execute Tasks on the foreign system
     *
     * @return void
     */
    protected function runTasks()
    {
        $sshConnection = SshConnection::makeInstance();
        $resultingMessages = $sshConnection->runForeignTasksCommandController();
        foreach ($resultingMessages as $resultMessage) {
            if (is_array($resultMessage)) {
                $resultMessage = implode(PHP_EOL, $resultMessage);
            }
            $this->logger->notice(
                'Task execution results',
                array(
                    'message' => $resultMessage,
                )
            );
        }
    }
}
