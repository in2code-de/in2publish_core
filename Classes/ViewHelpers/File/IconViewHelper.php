<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\File;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function explode;

class IconViewHelper extends AbstractViewHelper
{
    private const ARG_RECORD = 'record';

    protected IconFactory $iconFactory;

    protected IconRegistry $iconRegistry;

    protected $escapeOutput = false;

    public function injectIconFactory(IconFactory $iconFactory): void
    {
        $this->iconFactory = $iconFactory;
    }

    public function injectIconRegistry(IconRegistry $iconRegistry): void
    {
        $this->iconRegistry = $iconRegistry;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(self::ARG_RECORD, RecordInterface::class, 'The record to show the icon for', true);
    }

    public function render(): string
    {
        /** @var RecordInterface $record */
        $record = $this->arguments[self::ARG_RECORD];

        $mimeType = explode(',', $record->getMergedProperty('mime_type'))[0];
        $iconIdentifier = $mimeTypeIcon = $this->iconRegistry->getIconIdentifierForMimeType($mimeType);

        if ($mimeTypeIcon === null) {
            $extension = explode(',', $record->getMergedProperty('extension'))[0];
            $fileExtensionIcon = $this->iconRegistry->getIconIdentifierForFileExtension($extension);
            if ($fileExtensionIcon === 'mimetypes-other-other') {
                $mimeTypeIcon = $this->iconRegistry->getIconIdentifierForMimeType(explode('/', $mimeType)[0] . '/*');
                $iconIdentifier = $mimeTypeIcon ?? $fileExtensionIcon;
            } else {
                $iconIdentifier = $fileExtensionIcon;
            }
        }

        return $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render();
    }
}
