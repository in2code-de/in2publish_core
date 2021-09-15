<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController as ExtbaseActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

use function is_int;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class ActionController extends ExtbaseActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ConfigContainer */
    protected $configContainer;

    /** @var EnvironmentService */
    protected $environmentService;

    /**
     * Page ID
     * UID of the selected Page in the page tree
     *
     * @var int
     */
    protected $pid = 0;

    /**
     * @var bool
     */
    protected $forcePidInteger = true;

    /**
     * Creates a logger for the instantiated controller object.
     * When extending from this class and using an own constructor don't forget
     * to call this constructor method at the end of your own implementation
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        ConfigContainer $configContainer,
        ExecutionTimeService $executionTimeService,
        EnvironmentService $environmentService
    ) {
        $this->configContainer = $configContainer;
        $pid = BackendUtility::getPageIdentifier();
        if (true === $this->forcePidInteger && !is_int($pid)) {
            $this->logger->warning('Page identifier is not an int. Falling back to 0.', ['pid' => $this->pid]);
            $pid = 0;
        }
        $this->pid = $pid;
        $executionTimeService->start();
        $this->environmentService = $environmentService;
    }

    /**
     * Additionally creates the controller context if not done yet.
     * Do NOT call this Method before $this->initializeAction() is going to be executed
     * because it relies on $this->request and $this->response to be set
     *
     * @param string $messageBody
     * @param string $messageTitle
     * @param int $severity
     * @param bool $storeInSession
     *
     * @return void
     */
    public function addFlashMessage(
        $messageBody,
        $messageTitle = '',
        $severity = AbstractMessage::OK,
        $storeInSession = true
    ): void {
        if ($this->controllerContext === null) {
            $this->logger->debug('Prematurely building ControllerContext');
            $this->controllerContext = $this->buildControllerContext();
        }
        parent::addFlashMessage($messageBody, $messageTitle, $severity, $storeInSession);
    }

    protected function initializeView(ViewInterface $view): void
    {
        parent::initializeView($view);
        $this->view->assign('extensionVersion', ExtensionUtility::getExtensionVersion('in2publish_core'));
        $this->view->assign('config', $this->configContainer->get());
        $this->view->assign('testStatus', $this->environmentService->getTestStatus());
    }

    /**
     * Deactivate error messages in flash messages by explicitly returning false
     */
    protected function getErrorFlashMessage(): bool
    {
        return false;
    }
}
