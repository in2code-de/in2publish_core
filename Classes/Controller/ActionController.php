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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Service\ExecutionTimeService;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Utility\BackendUtility;
use In2code\In2publishCore\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class ActionController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ConfigContainer
     */
    protected $configContainer;

    /**
     * Page ID
     * UID of the selected Page in the page tree
     *
     * @var int
     */
    protected $pid = 0;

    /**
     * Creates a logger for the instantiated controller object.
     * When extending from this class and using an own constructor don't forget
     * to call this constructor method at the end of your own implementation
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $this->pid = BackendUtility::getPageIdentifier();
        GeneralUtility::makeInstance(ExecutionTimeService::class)->start();
    }

    /**
     * Additionally creates the controller context if not done yet.
     * Do NOT call this Method before $this->initializeAction() is going to be executed
     * because it relies on $this->request and $this->response to be set
     *
     * @param string $body
     * @param string $title
     * @param int $severity
     * @param bool $storeInSession
     * @return void
     */
    public function addFlashMessage($body, $title = '', $severity = AbstractMessage::OK, $storeInSession = true)
    {
        if ($this->controllerContext === null) {
            $this->logger->debug('Prematurely building ControllerContext');
            $this->controllerContext = $this->buildControllerContext();
        }
        parent::addFlashMessage($body, $title, $severity, $storeInSession);
    }

    /**
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);
        $this->view->assign('extensionVersion', ExtensionUtility::getExtensionVersion('in2publish_core'));
        $this->view->assign('config', $this->configContainer->get());
        $this->view->assign('testStatus', GeneralUtility::makeInstance(EnvironmentService::class)->getTestStatus());
    }

    /**
     * Deactivate error messages in flash messages by explicitly returning false
     *
     * @return string|bool
     */
    protected function getErrorFlashMessage()
    {
        return false;
    }
}
