<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Uri;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
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
use In2code\In2publishCore\Utility\UriUtility;
use In2code\In2publishCore\ViewHelpers\Link\PreviewRecordViewHelper;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function sprintf;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * @deprecated
 */
class PreviewAnyRecordViewHelper extends AbstractViewHelper
{
    protected const DEPRECATED_VIEWHELPER = 'The ViewHelper "%s" is deprecated and will be removed in in2publish_core version 11. Use %s instead.';

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('identifier', 'int', 'uid of the record to preview', true);
        $this->registerArgument('tableName', 'string', 'table name of the record to preview', true);
    }

    /**
     * Build uri for any record preview on [removed] and
     * respect settings of Page TSConfig TCEMAIN.preview
     *
     * @return false|string false if not configuration found, otherwise URI
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function render()
    {
        trigger_error(
            sprintf(self::DEPRECATED_VIEWHELPER, static::class, PreviewRecordViewHelper::class),
            E_USER_DEPRECATED
        );

        $identifier = $this->arguments['identifier'];
        $tableName = $this->arguments['tableName'];

        $uri = BackendUtility::buildPreviewUri($tableName, $identifier, 'local');
        if (null === $uri) {
            return '';
        }
        $uri = new Uri($uri);
        $uri = UriUtility::normalizeUri($uri);
        $uri = $uri->withScheme('')->withHost('');
        return (string)$uri;
    }
}
