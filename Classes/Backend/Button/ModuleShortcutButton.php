<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Backend\Button;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ExtbaseModule;
use TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function ucfirst;
use function version_compare;

class ModuleShortcutButton extends ShortcutButton
{
    public function setRequest(ServerRequestInterface $request): void
    {
        /** @var Route $route */
        $route = $request->getAttribute('route');
        $arguments = $request->getQueryParams();
        $pageId = $request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? null;

        $typo3Version = new Typo3Version();
        if (version_compare($typo3Version->getVersion(), '12', '<')) {
            $modConf = $route->getOption('moduleConfiguration');
            $displayName = LocalizationUtility::translate($modConf['labels'] . ':mlang_tabs_tab');
        } else {
            /**
             * @noinspection PhpUndefinedClassInspection
             * @var ExtbaseModule $module
             */
            $module = $route->getOption('module');
            $displayName = LocalizationUtility::translate($module->getTitle());
        }

        if (null !== $pageId) {
            if (0 === $pageId) {
                $displayName .= ' Root (ID 0)';
            } else {
                $arguments['id'] = $pageId;
                $displayName .= ' Page ' . $pageId;
            }
        }
        /** @var ExtbaseRequestParameters $extbase */
        $extbase = $request->getAttribute('extbase');
        $displayName .= ' ' . ucfirst($extbase->getControllerActionName());
        $this->setArguments($arguments);

        $this->setRouteIdentifier($route->getOption('_identifier'));
        $this->setDisplayName($displayName);
    }
}
