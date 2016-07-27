<?php
namespace In2code\In2publishCore\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class TableConfigurationArrayUtility
 *
 * @package In2code\In2publish\Utility
 */
class TableConfigurationArrayUtility
{
    /**
     * Returns all table names that are not in the exclusion list
     *
     * @param array $exceptTableNames
     * @return array
     * @internal param Record|null $record
     */
    public static function getAllTableNames(array $exceptTableNames = array())
    {
        $tableNames = self::getAllTableNamesFromTca();
        if (!empty($exceptTableNames)) {
            $tableNames = array_diff($tableNames, $exceptTableNames);
        }
        return $tableNames;
    }

    /**
     * Returns all table names that are not in the exclusion list and that have a pid field
     *
     * @param array $exceptTableNames
     * @return array
     * @internal param Record|null $record
     */
    public static function getAllTableNamesWithPidProperty(array $exceptTableNames = array())
    {
        $allTableNames = self::getAllTableNames($exceptTableNames);
        $database = DatabaseUtility::buildLocalDatabaseConnection();
        foreach ($allTableNames as &$tableName) {
            $fields = $database->admin_get_fields($tableName);
            if (!array_key_exists('pid', $fields)) {
                unset($tableName);
            }
        }
        return $allTableNames;
    }

    /**
     * Get all tableNames from TCA
     *
     * @return array
     */
    protected static function getAllTableNamesFromTca()
    {
        return array_keys(self::getTableConfigurationArray());
    }

    /**
     * @param string $path The path to follow, array of keys or dot-separated
     * @return mixed
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getTableConfigurationArray($path = '')
    {
        if ($path) {
            return ArrayUtility::getValueByPath($GLOBALS['TCA'], $path);
        }
        return $GLOBALS['TCA'];
    }

    /**
     * Returns all table names that are not in the exclusion list
     *
     * @param array $exceptTableNames
     * @return array
     */
    public static function getAllTableNamesAllowedOnRootLevel(array $exceptTableNames = array())
    {
        $rootLevelTables = array('pages');
        foreach (self::getTableConfigurationArray() as $tableName => $tableConfiguration) {
            if (!in_array($tableName, $exceptTableNames)) {
                if (!empty($tableConfiguration['ctrl']['rootLevel'])) {
                    if (in_array($tableConfiguration['ctrl']['rootLevel'], array(1, -1, true))) {
                        $rootLevelTables[] = $tableName;
                    }
                }
            }
        }
        return $rootLevelTables;
    }

    /**
     * Get title fieldname from table
     *
     * @param string $tableName
     * @return string
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getTitleFieldFromTable($tableName)
    {
        $titleField = '';
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['label'])) {
            $titleField = $GLOBALS['TCA'][$tableName]['ctrl']['label'];
        }
        return $titleField;
    }

    /**
     * Get sorting field from TCA definition
     *
     * @param string $tableName
     * @return string
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getSortingField($tableName)
    {
        $sortingField = '';
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['sortby'])) {
            $sortingField = $GLOBALS['TCA'][$tableName]['ctrl']['sortby'];
        } elseif (!empty($GLOBALS['TCA'][$tableName]['ctrl']['crdate'])) {
            $sortingField = $GLOBALS['TCA'][$tableName]['ctrl']['crdate'];
        }
        return $sortingField;
    }

    /**
     * Get deleted field from TCA definition
     *
     * @param string $tableName
     * @return string
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public static function getDeletedField($tableName)
    {
        $deleteField = '';
        if (!empty($GLOBALS['TCA'][$tableName]['ctrl']['delete'])) {
            $deleteField = $GLOBALS['TCA'][$tableName]['ctrl']['delete'];
        }
        return $deleteField;
    }

    /**
     * Get tablename from locallang and TCA definition
     *
     * @param string $tableName
     * @return string
     */
    public static function getTableLabel($tableName)
    {
        $tableConfiguration = self::getTableConfigurationArray();
        $languageService = self::getLanguageService();
        $label = ucfirst($tableName);

        if ($languageService !== null && !empty($tableConfiguration[$tableName]['ctrl']['title'])) {
            $localizationKey = $tableConfiguration[$tableName]['ctrl']['title'];
            $localizedLabel = $languageService->sL($localizationKey);
            if (!empty($localizedLabel)) {
                $label = $localizedLabel;
            }
        }

        return $label;
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    protected static function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
