<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Configuration;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_keys;
use function implode;
use function in_array;
use function strpos;
use function ucfirst;

class TcaService implements SingletonInterface
{
    protected const TYPE_ROOT = 'root';
    protected const TYPE_PAGE = 'page';
    /** @var array RunTime Cache */
    protected array $rtc = [];

    public function getRecordLabel(array $row, string $table): string
    {
        $labelField = $GLOBALS['TCA'][$table]['ctrl']['label'] ?? null;
        $labelAltField = $GLOBALS['TCA'][$table]['ctrl']['label_alt'] ?? null;
        $labelAltFields = [];
        if (null !== $labelAltField) {
            $labelAltFields = GeneralUtility::trimExplode(',', $labelAltField, true);
        }
        $labelAltForce = $GLOBALS['TCA'][$table]['ctrl']['label_alt_force'] ?? false;

        $labels = [];
        if (null !== $labelField && !empty($row[$labelField])) {
            $labels[] = $row[$labelField];
        }
        if (empty($labels) || true === $labelAltForce) {
            foreach ($labelAltFields as $labelAltField) {
                if (!empty($row[$labelAltField])) {
                    $labels[] = $row[$labelAltField];
                }
            }
        }
        return implode(', ', $labels);
    }

    /**
     * Get table name from locallang and TCA definition
     */
    public function getTableLabel(string $tableName): string
    {
        $label = ucfirst($tableName);

        $titleField = $GLOBALS['TCA'][$tableName]['ctrl']['title'] ?? null;

        if (null !== $titleField) {
            $localizedLabel = $GLOBALS['LANG']->sL($titleField);
            if (!empty($localizedLabel)) {
                $label = $localizedLabel;
            }
        }

        return $label;
    }

    public function isHiddenRootTable(string $tableName): bool
    {
        return isset($GLOBALS['TCA'][$tableName]['ctrl']['hideTable'], $GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'])
            && true === (bool)$GLOBALS['TCA'][$tableName]['ctrl']['hideTable']
            && in_array($GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'], [1, -1], true);
    }

    public function getTablesAllowedOnPage(int $pid, ?int $doktype): array
    {
        // The root page does not have a doktype. Just get all allowed tables.
        if (0 === $pid) {
            if (!isset($this->rtc[self::TYPE_ROOT])) {
                $this->rtc[self::TYPE_ROOT] = $this->getAllAllowedTableNames(self::TYPE_ROOT);
            }
            return $this->rtc[self::TYPE_ROOT];
        }

        $type = isset($GLOBALS['PAGES_TYPES'][$doktype]['allowedTables']) ? $doktype : 'default';
        $key = self::TYPE_PAGE . '_' . $type;

        if (!isset($this->rtc[$key])) {
            $allowedOnType = $this->getAllAllowedTableNames(self::TYPE_PAGE);
            $allowedOnDoktype = $GLOBALS['PAGES_TYPES'][$type]['allowedTables'];
            if (false === strpos($allowedOnDoktype, '*')) {
                foreach ($allowedOnType as $index => $table) {
                    if (!GeneralUtility::inList($allowedOnDoktype, $table)) {
                        unset($allowedOnType[$index]);
                    }
                }
            }

            $this->rtc[$key] = $allowedOnType;
        }
        return $this->rtc[$key];
    }

    /**
     * Finds all tables which are allowed on either self::TYPE_ROOT or self::TYPE_PAGE according to the table's TCA
     * 'rootLevel' setting.
     *
     * @param string $type
     * @return array<string>
     */
    protected function getAllAllowedTableNames(string $type): array
    {
        if (!isset($this->rtc['_types'])) {
            $allowed = [
                self::TYPE_ROOT => [],
                self::TYPE_PAGE => [],
            ];
            foreach (array_keys($GLOBALS['TCA']) as $table) {
                switch ('pages' === $table ? -1 : (int)($GLOBALS['TCA'][$table]['ctrl']['rootLevel'] ?? 0)) {
                    case -1:
                        $allowed[self::TYPE_ROOT][] = $table;
                        $allowed[self::TYPE_PAGE][] = $table;
                        break;
                    case 0:
                        $allowed[self::TYPE_PAGE][] = $table;
                        break;
                    case 1:
                        $allowed[self::TYPE_ROOT][] = $table;
                        break;
                }
            }
            $this->rtc['_types'] = $allowed;
        }
        return $this->rtc['_types'][$type];
    }

    public function getDeletedField(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['delete'];
        }
        return '';
    }

    public function getDisableField(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'];
        }
        return '';
    }

    public function getLanguageField(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['languageField'];
        }
        return '';
    }

    public function getTransOrigPointerField(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'];
        }
        return '';
    }
}
