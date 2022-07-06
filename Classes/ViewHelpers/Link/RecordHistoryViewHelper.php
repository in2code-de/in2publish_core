<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Link;

/*
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
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

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class RecordHistoryViewHelper extends AbstractTagBasedViewHelper
{
    protected UriBuilder $uriBuilder;
    protected $tagName = 'a';

    public function __construct(UriBuilder $uriBuilder)
    {
        parent::__construct();
        $this->uriBuilder = $uriBuilder;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('uid', 'int', 'uid of record to be edited', true);
        $this->registerArgument('table', 'string', 'target database table', true);
        $this->registerArgument('returnUrl', 'string', 'return to this URL after closing the edit dialog', false, '');
    }

    /**
     * @throws RouteNotFoundException
     */
    public function render(): string
    {
        if ($this->arguments['uid'] < 1) {
            return '';
        }
        if (empty($this->arguments['returnUrl'])) {
            $this->arguments['returnUrl'] = GeneralUtility::getIndpEnv('REQUEST_URI');
        }

        $params = [
            'element' => $this->arguments['table'] . ':' . $this->arguments['uid'],
            'returnUrl' => $this->arguments['returnUrl'],
        ];
        $uri = (string)$this->uriBuilder->buildUriFromRoute('record_history', $params);
        $this->tag->addAttribute('href', $uri);
        $this->tag->setContent($this->renderChildren());
        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }
}
