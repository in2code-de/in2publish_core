<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Record;

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
use In2code\In2publishCore\Domain\Model\RecordInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function array_filter;

class LanguageFlagIconViewHelper extends AbstractViewHelper
{
    private const ARG_RECORD = 'record';
    private const ARG_SIDE = 'side';

    protected $escapeOutput = false;

    protected IconFactory $iconFactory;

    protected TranslationConfigurationProvider $translateTools;

    protected BackendUserAuthentication $backendUser;

    public function __construct(IconFactory $iconFactory, TranslationConfigurationProvider $translateTools)
    {
        $this->iconFactory = $iconFactory;
        $this->translateTools = $translateTools;
        $this->backendUser = $GLOBALS['BE_USER'];
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(self::ARG_RECORD, Record::class, 'The record to get the flag for', true);
        $this->registerArgument(self::ARG_SIDE, 'string', '"local"/"foreign" as the language property side', true);
    }

    public function render(): string
    {
        /** @var Record $record */
        $record = $this->arguments[self::ARG_RECORD];

        $table = $record->getClassification();

        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
        if (null === $languageField) {
            return $this->iconFactory->getIcon('actions-pencil', Icon::SIZE_SMALL)->render();
        }

        $systemLanguages = array_filter(
            $this->translateTools->getSystemLanguages(),
            fn (array $languageRecord): bool => $this->backendUser->checkLanguageAccess($languageRecord['uid'])
        );

        $propsBySide = $record->getPropsBySide($this->arguments['side']);
        $language = $propsBySide[$languageField] ?? $record->getProp($languageField);

        $systemLanguage = $systemLanguages[$language] ?? null;
        if (null === $systemLanguage) {
            return $this->iconFactory->getIcon('flags-multiple', Icon::SIZE_SMALL, 'overlay-edit')->render();
        }

        return $this->iconFactory->getIcon(
            $systemLanguage['flagIcon'],
            Icon::SIZE_SMALL,
            'overlay-edit'
        )->render();
    }
}
