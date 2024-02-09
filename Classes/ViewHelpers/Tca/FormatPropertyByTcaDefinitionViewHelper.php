<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers\Tca;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

use function date;
use function htmlspecialchars;
use function nl2br;
use function str_starts_with;
use function trim;

use const ENT_QUOTES;

class FormatPropertyByTcaDefinitionViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;
    protected array $tableConfiguration = [];

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('fieldName', 'string', 'The field name to get the localized label from', true);
        $this->registerArgument('tableName', 'string', 'The table name in which the field can be found', true);
    }

    /**
     * Get formatted output by TCA definition
     */
    public function render(): string
    {
        $fieldName = $this->arguments['fieldName'];
        $tableName = $this->arguments['tableName'];

        $this->tableConfiguration = $GLOBALS['TCA'][$tableName]['columns'][$fieldName] ?? [];
        $value = $this->renderChildren();
        if (null === $value) {
            return '';
        }
        $value = trim((string)$value);

        if (empty($this->tableConfiguration['config']['type'])) {
            return $value;
        }

        switch ($this->tableConfiguration['config']['type']) {
            case 'input':
                $value = $this->changeValueForTypeInput($value);
                break;
            case 'text':
                $value = nl2br($value);
                break;
            case 'check':
                return $value ? '<pre>true</pre>' : '<pre>false</pre>';
            case 'select':
                $value = $this->changeValueForTypeSelect($value);
                break;
            default:
        }

        return htmlspecialchars($value, ENT_QUOTES);
    }

    protected function changeValueForTypeInput(string $value): string
    {
        $eval = $this->tableConfiguration['config']['eval'] ?? null;
        if (null === $eval) {
            return $value;
        }

        if (GeneralUtility::inList($eval, 'password')) {
            return '*******';
        }
        if (
            GeneralUtility::inList($eval, 'datetime')
            || GeneralUtility::inList($eval, 'date')
        ) {
            if ($value !== '0') {
                $value = date('Y-m-d H:i:s', (int)$value);
            }
        }
        return $value;
    }

    protected function changeValueForTypeSelect(string $value): string
    {
        $items = $this->tableConfiguration['config']['items'] ?? [];
        foreach ($items as $item) {
            $itemValue = (string)($item['value'] ?? $item[1] ?? '');
            if ($itemValue === $value) {
                $value = $item['label'] ?? $item[0] ?? '';
                if (str_starts_with($value, 'LLL')) {
                    $value = LocalizationUtility::translate($value) ?: $value;
                }
                return $value;
            }
        }
        return empty($value) ? $value : '[invalid value] (' . $value . ')';
    }
}
