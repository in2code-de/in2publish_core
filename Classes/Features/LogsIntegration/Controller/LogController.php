<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\LogsIntegration\Controller;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
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

use CoStack\Logs\Controller\LogController as LogsController;
use In2code\In2publishCore\Domain\Service\ExecutionTimeService;
use In2code\In2publishCore\Features\AdminTools\Controller\Traits\AdminToolsModuleTemplate;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3Fluid\Fluid\View\ViewInterface;

class LogController extends LogsController
{
    use AdminToolsModuleTemplate;

    public function __construct(ExecutionTimeService $executionTimeService)
    {
        $executionTimeService->start();
    }

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected function initializeAction(): void
    {
        parent::initializeAction();
        $this->logConfiguration = $GLOBALS['TYPO3_CONF_VARS']['LOG']['In2code']['In2publishCore'];
    }

    protected function resolveView(): ViewInterface
    {
        $view = parent::resolveView();
        if ($view instanceof TemplateView) {
            $templatePaths = $view->getTemplatePaths();
            $templatePaths->setTemplateRootPaths([
                0 => 'EXT:logs/Resources/Private/Templates/',
                10 => 'EXT:in2publish_core/Resources/Private/Templates/',
            ]);
            $templatePaths->setLayoutRootPaths([
                0 => 'EXT:logs/Resources/Private/Layouts/',
                10 => 'EXT:in2publish_core/Resources/Private/Layouts/',
            ]);
            $templatePaths->setPartialRootPaths([
                0 => 'EXT:logs/Resources/Private/Partials/',
                10 => 'EXT:in2publish_core/Resources/Private/Partials/',
            ]);
        }
        return $view;
    }
}
