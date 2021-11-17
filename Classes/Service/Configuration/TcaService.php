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

use Doctrine\DBAL\Schema\Table;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_diff;
use function array_keys;
use function implode;
use function in_array;
use function strpos;
use function ucfirst;

class TcaService implements SingletonInterface
{
    protected const TYPE_ROOT = 'root';
    protected const TYPE_PAGE = 'page';

    protected array $tableNames;

    /** @var array RunTime Cache */
    protected array $rtc = [];

    public function __construct()
    {
        $this->tableNames = array_keys($GLOBALS['TCA'] ?? []);
    }

    /**
     * @param string[] $exceptTableNames
     *
     * @return string[]
     *
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function getAllTableNamesAllowedOnRootLevel(array $exceptTableNames = []): array
    {
        $rootLevelTables = [];
        foreach ($GLOBALS['TCA'] ?? [] as $tableName => $tableConfiguration) {
            if (
                !empty($tableConfiguration['ctrl']['rootLevel'])
                && !in_array($tableName, $exceptTableNames, true)
                && in_array($tableConfiguration['ctrl']['rootLevel'], [1, -1, true], true)
            ) {
                $rootLevelTables[] = $tableName;
            }
        }

        // always add pages, even if they are excluded
        if (!in_array('pages', $rootLevelTables, true)) {
            $rootLevelTables[] = 'pages';
        }
        return $rootLevelTables;
    }

    /**
     * Get label field name from table
     *
     * @return string Field name of the configured label field or empty string if not set
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function getLabelFieldFromTable(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['label'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['label'];
        }
        return '';
    }

    /**
     * @return string Field name of the configured label_alt field or empty string if not set
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function getLabelAltFieldFromTable(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['label_alt'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['label_alt'];
        }
        return '';
    }

    /** @SuppressWarnings("PHPMD.Superglobals") */
    public function getLabelAltForceFromTable(string $tableName): bool
    {
        if (isset($GLOBALS['TCA'][$tableName]['ctrl']['label_alt_force'])) {
            return (bool)$GLOBALS['TCA'][$tableName]['ctrl']['label_alt_force'];
        }
        return false;
    }

    public function getRecordLabel(array $row, string $table): string
    {
        $labelField = $this->getLabelFieldFromTable($table);
        $labelAltField = $this->getLabelAltFieldFromTable($table);
        $labelAltFields = GeneralUtility::trimExplode(',', $labelAltField, true);
        $labelAltForce = $this->getLabelAltForceFromTable($table);

        $labels = [];
        if (!empty($row[$labelField])) {
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
     * @return string Field name of the configured title field or empty string if not set
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function getTitleFieldFromTable(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['title'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['title'];
        }
        return '';
    }

    /** @SuppressWarnings("PHPMD.Superglobals") */
    public function getSortingField(string $tableName): string
    {
        $sortingField = '';
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['sortby'])) {
            $sortingField = $GLOBALS['TCA'][$tableName]['ctrl']['sortby'];
        } elseif (!empty($GLOBALS['TCA'][$tableName]['ctrl']['crdate'])) {
            $sortingField = $GLOBALS['TCA'][$tableName]['ctrl']['crdate'];
        }
        return $sortingField;
    }

    /** @SuppressWarnings("PHPMD.Superglobals") */
    public function getNameOfSortingField(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['sortby'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['sortby'];
        }
        return '';
    }

    /** @SuppressWarnings("PHPMD.Superglobals") */
    public function getDeletedField(string $tableName): string
    {
        $deleteField = '';
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
            $deleteField = $GLOBALS['TCA'][$tableName]['ctrl']['delete'];
        }
        return $deleteField;
    }

    /**
     * Records whose deleted field evaluate to true will not be shown in the frontend.
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function getDisableField(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['disabled'];
        }
        return '';
    }

    /** @SuppressWarnings("PHPMD.Superglobals") */
    public function getLanguageField(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['languageField'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['languageField'];
        }
        return '';
    }

    /** @SuppressWarnings("PHPMD.Superglobals") */
    public function getTransOrigPointerField(string $tableName): string
    {
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'])) {
            return $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'];
        }
        return '';
    }

    /**
     * Returns all table names that are not in the exclusion list and that have
     * a pid and uid field
     * TODO: Cache the result because `$database->getSchemaManager()->listTables()` is expensive
     *
     * @param string[] $exceptTableNames
     *
     * @return string[]
     */
    public function getAllTableNamesWithPidAndUidField(array $exceptTableNames = []): array
    {
        $result = [];

        $tables = $this->getDatabaseSchemaTables();

        foreach ($tables as $table) {
            if (
                $table->hasColumn('uid')
                && $table->hasColumn('pid')
                && !in_array($table->getName(), $exceptTableNames, true)
            ) {
                $result[] = $table->getName();
            }
        }

        return $result;
    }

    /** @SuppressWarnings("PHPMD.Superglobals") */
    public function getConfigurationArrayForTable(string $table): ?array
    {
        return $GLOBALS['TCA'][$table] ?? null;
    }

    /** @SuppressWarnings("PHPMD.Superglobals") */
    public function getColumnConfigurationForTableColumn(string $table, string $column): ?array
    {
        return $GLOBALS['TCA'][$table]['columns'][$column] ?? null;
    }

    /**
     * Returns all table names that are not in the exclusion list
     *
     * @param string[] $exceptTableNames
     */
    public function getAllTableNames(array $exceptTableNames = []): array
    {
        if (!empty($exceptTableNames)) {
            return array_diff($this->tableNames, $exceptTableNames);
        }
        return $this->tableNames;
    }

    /**
     * Get table name from locallang and TCA definition
     */
    public function getTableLabel(string $tableName): string
    {
        $label = ucfirst($tableName);

        $titleField = $this->getTitleFieldFromTable($tableName);

        if ('' !== $titleField) {
            $localizedLabel = $this->localizeLabel($titleField);
            if (!empty($localizedLabel)) {
                $label = $localizedLabel;
            }
        }

        return $label;
    }

    /** @SuppressWarnings("PHPMD.Superglobals") */
    public function isHiddenRootTable(string $tableName): bool
    {
        return isset($GLOBALS['TCA'][$tableName]['ctrl']['hideTable'], $GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'])
               && true === (bool)$GLOBALS['TCA'][$tableName]['ctrl']['hideTable']
               && in_array($GLOBALS['TCA'][$tableName]['ctrl']['rootLevel'], [1, -1], true);
    }

    /**
     * @SuppressWarnings("PHPMD.Superglobals")
     * @codeCoverageIgnore
     */
    protected function localizeLabel(string $label): string
    {
        if ($GLOBALS['LANG'] instanceof LanguageService) {
            return $GLOBALS['LANG']->sL($label);
        }
        return '';
    }

    /**
     * @return Table[]
     */
    public function getDatabaseSchemaTables(): array
    {
        $tables = [];
        $database = DatabaseUtility::buildLocalDatabaseConnection();
        if ($database) {
            $tables = $database->getSchemaManager()->listTables();
        }
        return $tables;
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
}
