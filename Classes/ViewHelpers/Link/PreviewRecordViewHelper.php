<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Link;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
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

use In2code\In2publishCore\Utility\BackendUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

class PreviewRecordViewHelper extends AbstractTagBasedViewHelper
{
    public const ARG_IDENTIFIER = 'identifier';
    public const ARG_STAGING_LEVEL = 'stagingLevel';
    public const ARG_TABLE = 'table';

    protected $tagName = 'a';

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerTagAttribute('name', 'string', 'Specifies the name of an anchor');
        $this->registerTagAttribute(
            'rel',
            'string',
            'Specifies the relationship between the current document and the linked document'
        );
        $this->registerTagAttribute(
            'rev',
            'string',
            'Specifies the relationship between the linked document and the current document'
        );
        $this->registerTagAttribute('target', 'string', 'Specifies where to open the linked document');
        $this->registerUniversalTagAttributes();
        $this->registerArgument(self::ARG_IDENTIFIER, 'integer', 'UID the the page to preview');
        $this->registerArgument(self::ARG_STAGING_LEVEL, 'string', '"local" or "foreign"');
        $this->registerArgument(self::ARG_TABLE, 'string', 'the records\' table name');
    }

    public function render()
    {
        $uri = BackendUtility::buildPreviewUri(
            $this->arguments[self::ARG_TABLE],
            $this->arguments[self::ARG_IDENTIFIER],
            $this->arguments[self::ARG_STAGING_LEVEL]
        );
        if (null === $uri) {
            return '&nbsp;';
        }

        $this->tag->addAttribute('href', (string)$uri);
        $this->tag->setContent($this->renderChildren());
        return $this->tag->render();
    }
}
