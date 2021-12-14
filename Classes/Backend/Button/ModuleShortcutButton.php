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
use TYPO3\CMS\Backend\Template\Components\Buttons\Action\ShortcutButton;
use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ModuleShortcutButton extends ShortcutButton
{
    public function setRequest(ServerRequestInterface $request): void
    {
        /** @var Route $route */
        $route = $request->getAttribute('route');
        $modConf = $route->getOption('moduleConfiguration');
        $pageId = $request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? null;
        $displayName = LocalizationUtility::translate($modConf['labels'] . ':mlang_tabs_tab');

        if (null !== $pageId) {
            if (0 === $pageId) {
                $displayName .= ' Root (ID 0)';
            } else {
                $this->setArguments(['id' => $pageId]);
                $displayName .= ' Page ' . $pageId;
            }
        }

        $this->setRouteIdentifier($route->getOption('_identifier'));
        $this->setDisplayName($displayName);
    }
}
