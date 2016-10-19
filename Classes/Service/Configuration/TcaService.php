<?php
namespace In2code\In2publishCore\Service\Configuration;

/***************************************************************
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
 ***************************************************************/

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class TableConfigurationArrayService
 */
class TcaService implements SingletonInterface
{
    /**
     * @var array[]
     */
    protected $tca = array();

    /**
     * @var array
     */
    protected $tableNames = array();

    /**
     * TcaService constructor.
     */
    public function __construct()
    {
        $this->tca = $this->getTca();
        $this->tableNames = array_keys($this->tca);
    }

    /**
     * @param array $exceptTableNames
     * @return array
     */
    public function getAllTableNamesAllowedOnRootLevel(array $exceptTableNames = array())
    {
        $rootLevelTables = array();
        foreach ($this->tca as $tableName => $tableConfiguration) {
            if (!in_array($tableName, $exceptTableNames)) {
                if (!empty($tableConfiguration['ctrl']['rootLevel'])) {
                    if (in_array($tableConfiguration['ctrl']['rootLevel'], array(1, -1, true))) {
                        $rootLevelTables[] = $tableName;
                    }
                }
            }
        }

        // always add pages, even if they are excluded
        if (!in_array('pages', $rootLevelTables)) {
            $rootLevelTables[] = 'pages';
        }
        return $rootLevelTables;
    }

    /**
     * Get label field name from table
     *
     * @param string $tableName
     * @return string Field name of the configured label field or empty string if not set
     */
    public function getLabelFieldFromTable($tableName)
    {
        $labelField = '';
        if (!empty($this->tca[$tableName]['ctrl']['label'])) {
            $labelField = $this->tca[$tableName]['ctrl']['label'];
        }
        return $labelField;
    }

    /**
     * Get label_alt field name from table
     *
     * @param string $tableName
     * @return string Field name of the configured label_alt field or empty string if not set
     */
    public function getLabelAltFieldFromTable($tableName)
    {
        $labelAltField = '';
        if (!empty($this->tca[$tableName]['ctrl']['label_alt'])) {
            $labelAltField = $this->tca[$tableName]['ctrl']['label_alt'];
        }
        return $labelAltField;
    }

    /**
     * Get title field name from table
     *
     * @param string $tableName
     * @return string Field name of the configured title field or empty string if not set
     */
    public function getTitleFieldFromTable($tableName)
    {
        $titleField = '';
        if (!empty($this->tca[$tableName]['ctrl']['title'])) {
            $titleField = $this->tca[$tableName]['ctrl']['title'];
        }
        return $titleField;
    }

    /**
     * Get sorting field from TCA definition
     *
     * @param string $tableName
     * @return string
     */
    public function getSortingField($tableName)
    {
        $sortingField = '';
        if (!empty($this->tca[$tableName]['ctrl']['sortby'])) {
            $sortingField = $this->tca[$tableName]['ctrl']['sortby'];
        } elseif (!empty($this->tca[$tableName]['ctrl']['crdate'])) {
            $sortingField = $this->tca[$tableName]['ctrl']['crdate'];
        }
        return $sortingField;
    }

    /**
     * Get deleted field from TCA definition
     *
     * @param string $tableName
     * @return string
     */
    public function getDeletedField($tableName)
    {
        $deleteField = '';
        if (!empty($this->tca[$tableName]['ctrl']['delete'])) {
            $deleteField = $this->tca[$tableName]['ctrl']['delete'];
        }
        return $deleteField;
    }

    /**
     * Returns all table names that are not in the exclusion list and that have a pid field
     *
     * @param array $exceptTableNames
     * @return array
     */
    public function getAllTableNamesWithPidField(array $exceptTableNames = array())
    {
        $databaseSchema = $this->getDatabaseSchema();
        $allTableNames = array();

        foreach (array_keys($databaseSchema) as $tableName) {
            if (isset($databaseSchema[$tableName]['pid'])) {
                $allTableNames[] = $tableName;
            }
        }

        if (!empty($exceptTableNames)) {
            return array_diff($allTableNames, $exceptTableNames);
        }

        return $allTableNames;
    }

    /**
     * @param string $table
     * @return array|null
     */
    public function getConfigurationArrayForTable($table)
    {
        if (isset($this->tca[$table])) {
            return $this->tca[$table];
        }
        return null;
    }

    /**
     * @param string $table
     * @param string $column
     * @return array|null
     */
    public function getColumnConfigurationForTableColumn($table, $column)
    {
        if (isset($this->tca[$table]['columns'][$column])) {
            return $this->tca[$table]['columns'][$column];
        }
        return null;
    }

    /**
     * Returns all table names that are not in the exclusion list
     *
     * @param array $exceptTableNames
     * @return array
     */
    public function getAllTableNames(array $exceptTableNames = array())
    {
        if (!empty($exceptTableNames)) {
            return array_diff($this->tableNames, $exceptTableNames);
        }
        return $this->tableNames;
    }

    /**
     * Get table name from locallang and TCA definition
     *
     * @param string $tableName
     * @return string
     */
    public function getTableLabel($tableName)
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

    /**
     * @param string $tableName
     * @return bool
     */
    public function isHiddenRootTable($tableName)
    {
        return isset($this->tca[$tableName]['ctrl']['hideTable'])
               && isset($this->tca[$tableName]['ctrl']['rootLevel'])
               && true === (bool)$this->tca[$tableName]['ctrl']['hideTable']
               && in_array($this->tca[$tableName]['ctrl']['rootLevel'], array(1, -1));
    }

    /**
     * @return array[]
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected function getTca()
    {
        return isset($GLOBALS['TCA']) ? $GLOBALS['TCA'] : array();
    }

    /**
     * @param string $label
     * @return string
     * @SuppressWarnings("PHPMD.Superglobals")
     * @codeCoverageIgnore
     */
    protected function localizeLabel($label)
    {
        if ($GLOBALS['LANG'] instanceof LanguageService) {
            return $GLOBALS['LANG']->sL($label);
        }
        return '';
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    protected function getDatabaseSchema()
    {
        return GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Service\\Database\\DatabaseSchemaService'
        )->getDatabaseSchema();
    }
}
