<?php
namespace In2code\In2publishCore\Testing\Tests\Database;

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

use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class DatabaseDifferencesTest
 */
class DatabaseDifferencesTest implements TestCaseInterface
{
    /**
     * @return TestResult
     */
    public function run()
    {
        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();

        if ($this->areDifferentDatabases($localDatabase, $foreignDatabase)) {
            return new TestResult('database.local_and_foreign_identical', TestResult::ERROR);
        }

        $tableInfoBlackList = array_flip(
            array(
                'Version',
                'Rows',
                'Avg_row_length',
                'Data_length',
                'Max_data_length',
                'Index_length',
                'Data_free',
                'Auto_increment',
                'Create_time',
                'Update_time',
                'Check_time',
                'Checksum',
                'Comment',
            )
        );

        $fieldInfoBlackList = array_flip(
            array(
                'Create_time',
                'Rows',
                'Avg_row_length',
                'Auto_increment',
                'Data_length',
                'Index_length',
                'Data_free',
                'Update_time',
                'Check_time',
                'Comment',
            )
        );

        $localTableInfo = $this->readTableStructure($localDatabase, $tableInfoBlackList, $fieldInfoBlackList);
        $foreignTableInfo = $this->readTableStructure($foreignDatabase, $tableInfoBlackList, $fieldInfoBlackList);

        $localTables = array_keys($localTableInfo);
        $foreignTables = array_keys($foreignTableInfo);

        $tablesOnlyOnLocal = array_diff($localTables, $foreignTables);
        $tablesOnlyOnForeign = array_diff($foreignTables, $localTables);

        if (!empty($tablesOnlyOnLocal) || !empty($tablesOnlyOnForeign)) {
            return new TestResult(
                'database.tables_missing_on_other_side',
                TestResult::ERROR,
                array_merge(
                    array('database.tables_only_on_local'),
                    $tablesOnlyOnLocal,
                    array('database.tables_only_on_foreign'),
                    $tablesOnlyOnForeign
                )
            );
        }

        $diffOnLocal = $this->identifyDifferences($localTableInfo, $foreignTableInfo);
        $diffOnForeign = $this->identifyDifferences($foreignTableInfo, $localTableInfo);

        if (!empty($diffOnLocal) || !empty($diffOnForeign)) {
            $fieldDifferences = array();
            $tableDifferences = array();

            foreach ($diffOnLocal as $tableName => $fieldArray) {
                if (isset($fieldArray['fields']) && is_array($fieldArray['fields'])) {
                    foreach ($fieldArray['fields'] as $fieldName => $fieldProperties) {
                        $fieldExistsOnLocal = isset($localTableInfo[$tableName]['fields'][$fieldName]);
                        $fieldExistsOnForeign = isset($foreignTableInfo[$tableName]['fields'][$fieldName]);

                        if ($fieldExistsOnForeign && $fieldExistsOnLocal) {
                            foreach ($fieldProperties as $propertyType => $propertyValue) {
                                $fieldDifferences[] = $tableName . '.' . $fieldName . '.' . $propertyType . ': Local: '
                                                      . $propertyValue . ' Foreign: '
                                                      . $diffOnForeign[$tableName]['fields'][$fieldName][$propertyType];
                            }
                        } elseif ($fieldExistsOnForeign && !$fieldExistsOnLocal) {
                            $fieldDifferences[] = $tableName . '.' . $fieldName . ': Only exists on foreign';
                        } elseif (!$fieldExistsOnForeign && $fieldExistsOnLocal) {
                            $fieldDifferences[] = $tableName . '.' . $fieldName . ': Only exists on local';
                        } else {
                            continue;
                        }

                        unset($diffOnLocal[$tableName]['fields'][$fieldName]);
                        unset($diffOnForeign[$tableName]['fields'][$fieldName]);
                    }
                }

                if (isset($fieldArray['table']) && is_array($fieldArray['table'])) {
                    foreach ($fieldArray['table'] as $propertyName => $fieldProperties) {
                        $propertyExistsLocal = isset($localTableInfo[$tableName]['table'][$propertyName]);
                        $propertyExistsForeign = isset($foreignTableInfo[$tableName]['table'][$propertyName]);

                        if ($propertyExistsLocal && $propertyExistsForeign) {
                            if ($localTableInfo[$tableName]['table'][$propertyName]
                                !== $foreignTableInfo[$tableName]['table'][$propertyName]
                            ) {
                                $tableDifferences[] = $tableName . '.' . $propertyName . ': Local: '
                                                      . $localTableInfo[$tableName]['table'][$propertyName]
                                                      . ' Foreign: '
                                                      . $foreignTableInfo[$tableName]['table'][$propertyName];
                            }
                        } elseif ($propertyExistsLocal && !$propertyExistsForeign) {
                            $tableDifferences[] = 'Table property ' . $tableName . '.' . $propertyName
                                                  . ': Only exists on foreign';
                        } elseif (!$propertyExistsLocal && $propertyExistsForeign) {
                            $tableDifferences[] = 'Table property ' . $tableName . '.' . $propertyName
                                                  . ': Only exists on local';
                        } else {
                            continue;
                        }

                        unset($diffOnLocal[$tableName]['table'][$propertyName]);
                        unset($diffOnForeign[$tableName]['table'][$propertyName]);
                    }
                }
            }

            foreach ($diffOnForeign as $tableName => $fieldArray) {
                if (isset($fieldArray['fields']) && is_array($fieldArray['fields'])) {
                    foreach (array_keys($fieldArray['fields']) as $fieldOnlyOnForeign) {
                        $fieldDifferences[] = $tableName . '.' . $fieldOnlyOnForeign . ': Only exists on foreign';
                    }
                }
                if (isset($fieldArray['table']) && is_array($fieldArray['fields'])) {
                    foreach (array_keys($fieldArray['table']) as $propertyOnlyOnForeign) {
                        $fieldDifferences[] = 'Table propety ' . $tableName . '.' . $propertyOnlyOnForeign
                                              . ': Only exists on foreign';
                    }
                }
            }

            return new TestResult(
                'database.field_differences',
                TestResult::ERROR,
                array_merge(
                    array('database.different_fields'),
                    $fieldDifferences,
                    array('database.different_tables'),
                    $tableDifferences
                )
            );
        }

        return new TestResult('database.no_differences');
    }

    /**
     * @param array $left
     * @param array $right
     * @return array
     */
    public function identifyDifferences(array $left, array $right)
    {
        $differences = array();

        foreach ($left as $leftKey => $leftValue) {
            if (array_key_exists($leftKey, $right)) {
                if (is_array($leftValue)) {
                    $subDifferences = $this->identifyDifferences($leftValue, $right[$leftKey]);
                    if (count($subDifferences)) {
                        $differences[$leftKey] = $subDifferences;
                    }
                } else {
                    if ($leftValue !== $right[$leftKey]) {
                        $differences[$leftKey] = $leftValue;
                    }
                }
            } else {
                $differences[$leftKey] = $leftValue;
            }
        }
        return $differences;
    }

    /**
     * @param DatabaseConnection $local
     * @param DatabaseConnection $foreign
     * @return bool
     */
    protected function areDifferentDatabases(DatabaseConnection $local, DatabaseConnection $foreign)
    {
        $random = (int)mt_rand(1, PHP_INT_MAX);
        $local->exec_INSERTquery(
            'tx_in2code_in2publish_task',
            array(
                'task_type' => 'Backend Test',
                'configuration' => $random,
            )
        );
        $uid = (int)$local->sql_insert_id();
        $results = (array)$foreign->exec_SELECTgetRows(
            '*',
            'tx_in2code_in2publish_task',
            'task_type LIKE "Backend Test"'
        );

        $identical = false;
        foreach ($results as $result) {
            if ($uid === (int)$result['uid'] && $random === (int)$result['configuration']) {
                $identical = true;
                break;
            }
        }
        $local->exec_DELETEquery('tx_in2code_in2publish_task', 'task_type LIKE "Backend Test"');
        $foreign->exec_DELETEquery('tx_in2code_in2publish_task', 'task_type LIKE "Backend Test"');

        return $identical;
    }

    /**
     * @param DatabaseConnection $database
     * @param array $tableInfoBlackList
     * @param array $fieldInfoBlackList
     * @return array
     */
    protected function readTableStructure(
        DatabaseConnection $database,
        array $tableInfoBlackList,
        array $fieldInfoBlackList
    ) {
        $tableStructure = array();
        $tables = $database->admin_get_tables();

        foreach ($tables as $tableName => $tableInfo) {
            // ignore deleted tables
            if (0 === strpos($tableName, 'zzz_')) {
                continue;
            }
            $fieldStructure = array();

            $fields = $database->admin_get_fields($tableName);
            foreach ($fields as $fieldName => $fieldInfo) {
                $fieldStructure[$fieldName] = array_diff_key($fieldInfo, $fieldInfoBlackList);
            }

            $tableStructure[$tableName] = array(
                'table' => array_diff_key($tableInfo, $tableInfoBlackList),
                'fields' => $fieldStructure,
            );
        }

        return $tableStructure;
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return array(
            'In2code\\In2publishCore\\Testing\\Tests\\Database\\LocalDatabaseTest',
            'In2code\\In2publishCore\\Testing\\Tests\\Database\\ForeignDatabaseTest',
        );
    }
}
