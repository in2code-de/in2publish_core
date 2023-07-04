<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\AdminTools\Controller\Traits;

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

use In2code\In2publishCore\Controller\Traits\CommonViewVariables;
use In2code\In2publishCore\Controller\Traits\ControllerModuleTemplate;
use In2code\In2publishCore\Features\AdminTools\Backend\Button\AdminToolButton;
use In2code\In2publishCore\Features\AdminTools\Service\ToolsRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @property UriBuilder $uriBuilder
 * @property Request $request
 */
trait AdminToolsModuleTemplate
{
    use CommonViewVariables;
    use ControllerModuleTemplate {
        render as renderModuleTemplate;
    }

    protected ToolsRegistry $toolsRegistry;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectToolsRegistry(ToolsRegistry $toolsRegistry): void
    {
        $this->toolsRegistry = $toolsRegistry;
    }

    protected function render(): string
    {
        $this->moduleTemplate->setModuleClass('tx_in2publishcore_admintools');
        $docHeader = $this->moduleTemplate->getDocHeaderComponent();
        $buttonBar = $docHeader->getButtonBar();

        foreach ($this->toolsRegistry->getEntries() as $entry) {
            $button = GeneralUtility::makeInstance(AdminToolButton::class);
            $button->setTitle(LocalizationUtility::translate($entry['name']) ?: $entry['name']);
            $button->setHref(
                $this->uriBuilder->reset()->uriFor(
                    GeneralUtility::trimExplode(',', $entry['action'])[0],
                    null,
                    $entry['alias']
                )
            );
            if (
                $this->request->getControllerObjectName() === $entry['controller']
                && in_array($this->request->getControllerActionName(), explode(',', $entry['action']))
            ) {
                $button->setClasses('btn-primary');
            }
            $buttonBar->addButton($button);
        }
        return $this->renderModuleTemplate();
    }
}
