<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller\Traits;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Backend\Button\ModuleShortcutButton;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;

use function str_replace;
use function strtolower;

/**
 * @property Request $request
 */
trait ControllerModuleTemplate
{
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected ModuleTemplate $moduleTemplate;

    public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory): void
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    public function processRequest(RequestInterface $request): ResponseInterface
    {
        if ($request instanceof ServerRequestInterface) {
            $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
            $this->moduleTemplate->setModuleId(strtolower(str_replace('\\', '_', static::class)));
        }
        return parent::processRequest($request);
    }

    protected function htmlResponse(string $html = null): ResponseInterface
    {
        return $this->responseFactory->createResponse()
                                     ->withHeader('Content-Type', 'text/html; charset=utf-8')
                                     ->withBody($this->streamFactory->createStream($html ?? $this->render()));
    }

    protected function jsonResponse(string $json = null): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream($json ?? $this->render()));
    }

    protected function render(): string
    {
        $docHeader = $this->moduleTemplate->getDocHeaderComponent();
        $buttonBar = $docHeader->getButtonBar();

        $moduleShortcutButton = GeneralUtility::makeInstance(ModuleShortcutButton::class);
        $moduleShortcutButton->setRequest($this->request);
        $buttonBar->addButton($moduleShortcutButton);

        $this->moduleTemplate->setContent($this->view->render());
        return $this->moduleTemplate->renderContent();
    }
}
