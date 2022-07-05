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

use In2code\In2publishCore\Domain\Model\Record;
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
        $this->registerArgument(self::ARG_RECORD, Record::class, 'The record to show the icon for', true);
    }

    public function render(): string
    {
        /** @var Record $record */
        $record = $this->arguments[self::ARG_RECORD];

        $iconIdentifier = $this->getIconIdentifier($record);

        return $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render();
    }

    protected function getIconIdentifier(Record $record): ?string
    {
        $mimeType = $record->getProp('mime_type');
        if ($mimeType === null) {
            return $this->getIconIdentifierForFileExtension($record);
        }

        $iconIdentifier = $this->iconRegistry->getIconIdentifierForMimeType($mimeType);

        if ($iconIdentifier === null) {
            $fileExtensionIcon = $this->getIconIdentifierForFileExtension($record);
            if ($fileExtensionIcon === 'mimetypes-other-other') {
                $iconIdentifier = $this->iconRegistry->getIconIdentifierForMimeType(explode('/', $mimeType)[0] . '/*');
                return $iconIdentifier ?? $fileExtensionIcon;
            }
            return $fileExtensionIcon;
        }
        return $iconIdentifier;
    }

    protected function getIconIdentifierForFileExtension(Record $record): string
    {
        $extension = $record->getProp('extension');
        return $this->iconRegistry->getIconIdentifierForFileExtension($extension);
    }
}
