<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Database;

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

use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;

use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function count;
use function is_array;
use function mt_rand;
use function strpos;

use const PHP_INT_MAX;

class DatabaseDifferencesTest implements TestCaseInterface
{
    public function run(): TestResult
    {
        $localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();

        if ($this->areDifferentDatabases($localDatabase, $foreignDatabase)) {
            return new TestResult('database.local_and_foreign_identical', TestResult::ERROR);
        }

        $localTableInfo = $this->readTableStructure($localDatabase);
        $foreignTableInfo = $this->readTableStructure($foreignDatabase);

        $localTables = array_keys($localTableInfo);
        $foreignTables = array_keys($foreignTableInfo);

        $tablesOnlyOnLocal = array_diff($localTables, $foreignTables);
        $tablesOnlyOnForeign = array_diff($foreignTables, $localTables);

        if (!empty($tablesOnlyOnLocal) || !empty($tablesOnlyOnForeign)) {
            return new TestResult(
                'database.tables_missing_on_other_side',
                TestResult::ERROR,
                array_merge(
                    ['database.tables_only_on_local'],
                    $tablesOnlyOnLocal,
                    ['database.tables_only_on_foreign'],
                    $tablesOnlyOnForeign
                )
            );
        }

        $diffOnLocal = $this->identifyDifferences($localTableInfo, $foreignTableInfo);
        $diffOnForeign = $this->identifyDifferences($foreignTableInfo, $localTableInfo);

        if (!empty($diffOnLocal) || !empty($diffOnForeign)) {
            $fieldDifferences = [];
            $tableDifferences = [];

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

                        unset(
                            $diffOnLocal[$tableName]['fields'][$fieldName],
                            $diffOnForeign[$tableName]['fields'][$fieldName]
                        );
                    }
                }

                if (isset($fieldArray['table']) && is_array($fieldArray['table'])) {
                    foreach ($fieldArray['table'] as $propertyName => $fieldProperties) {
                        $propExistsLocal = isset($localTableInfo[$tableName]['table'][$propertyName]);
                        $propExistsForeign = isset($foreignTableInfo[$tableName]['table'][$propertyName]);

                        if ($propExistsLocal && $propExistsForeign) {
                            if (
                                $localTableInfo[$tableName]['table'][$propertyName]
                                !== $foreignTableInfo[$tableName]['table'][$propertyName]
                            ) {
                                $tableDifferences[] = $tableName . '.' . $propertyName . ': Local: '
                                                      . $localTableInfo[$tableName]['table'][$propertyName]
                                                      . ' Foreign: '
                                                      . $foreignTableInfo[$tableName]['table'][$propertyName];
                            }
                        } elseif ($propExistsLocal && !$propExistsForeign) {
                            $tableDifferences[] = 'Table property ' . $tableName . '.' . $propertyName
                                                  . ': Only exists on foreign';
                        } elseif (!$propExistsLocal && $propExistsForeign) {
                            $tableDifferences[] = 'Table property ' . $tableName . '.' . $propertyName
                                                  . ': Only exists on local';
                        } else {
                            continue;
                        }

                        unset(
                            $diffOnLocal[$tableName]['table'][$propertyName],
                            $diffOnForeign[$tableName]['table'][$propertyName]
                        );
                    }
                }
            }

            foreach ($diffOnForeign as $tableName => $fieldArray) {
                if (is_array($fieldArray['fields']) && isset($fieldArray['fields'])) {
                    foreach (array_keys($fieldArray['fields']) as $fieldOnlyOnForeign) {
                        $fieldDifferences[] = $tableName . '.' . $fieldOnlyOnForeign . ': Only exists on foreign';
                    }
                }
                if (is_array($fieldArray['fields']) && isset($fieldArray['table'])) {
                    foreach (array_keys($fieldArray['table']) as $propOnlyOnForeign) {
                        $fieldDifferences[] = 'Table property ' . $tableName . '.' . $propOnlyOnForeign
                                              . ': Only exists on foreign';
                    }
                }
            }

            return new TestResult(
                'database.field_differences',
                TestResult::ERROR,
                array_merge(
                    ['database.different_fields'],
                    $fieldDifferences,
                    ['database.different_tables'],
                    $tableDifferences
                )
            );
        }

        return new TestResult('database.no_differences');
    }

    public function identifyDifferences(array $left, array $right): array
    {
        $differences = [];

        foreach ($left as $leftKey => $leftValue) {
            if (array_key_exists($leftKey, $right)) {
                if (is_array($leftValue)) {
                    $subDifferences = $this->identifyDifferences($leftValue, $right[$leftKey]);
                    if (count($subDifferences)) {
                        $differences[$leftKey] = $subDifferences;
                    }
                } elseif ($leftValue !== $right[$leftKey]) {
                    $differences[$leftKey] = $leftValue;
                }
            } else {
                $differences[$leftKey] = $leftValue;
            }
        }
        return $differences;
    }

    protected function areDifferentDatabases(Connection $local, Connection $foreign): bool
    {
        $random = mt_rand(1, PHP_INT_MAX);
        $local->insert(
            'tx_in2code_in2publish_task',
            [
                'task_type' => 'Backend Test',
                'configuration' => $random,
            ]
        );
        $uid = (int)$local->lastInsertId();
        $query = $foreign->createQueryBuilder();
        $statement = $query->select('*')
                           ->from('tx_in2code_in2publish_task')
                           ->where($query->expr()->eq('task_type', $query->createNamedParameter('Backend Test')))
                           ->execute();
        $identical = false;
        while ($result = $statement->fetchAssociative()) {
            if ($uid === (int)$result['uid'] && $random === (int)$result['configuration']) {
                $identical = true;
                break;
            }
        }
        $local->delete('tx_in2code_in2publish_task', ['task_type' => 'Backend Test']);
        $foreign->delete('tx_in2code_in2publish_task', ['task_type' => 'Backend Test']);

        return $identical;
    }

    protected function readTableStructure(Connection $database): array
    {
        $tableStructure = [];
        $tables = $database->getSchemaManager()->listTables();

        foreach ($tables as $table) {
            $tableName = $table->getName();
            // ignore deleted tables
            if (0 === strpos($tableName, 'zzz_')) {
                continue;
            }
            $fieldStructure = [];

            $fields = $database->getSchemaManager()->listTableColumns($tableName);
            foreach ($fields as $field) {
                $fieldName = $field->getName();
                $fieldStructure[$fieldName] = [
                    'length' => $field->getLength(),
                    'unsigned' => $field->getUnsigned(),
                    'scale' => $field->getScale(),
                    'precision' => $field->getPrecision(),
                    'notnull' => $field->getNotnull(),
                    'fixed' => $field->getFixed(),
                    'default' => $field->getDefault(),
                    'type' => $field->getType(),
                    'comment' => $field->getComment(),
                ];
            }

            $tableOptions = $table->getOptions();
            unset($tableOptions['autoincrement'], $tableOptions['comment']);
            $tableStructure[$tableName] = [
                'table' => $tableOptions,
                'fields' => $fieldStructure,
            ];
        }

        return $tableStructure;
    }

    public function getDependencies(): array
    {
        return [
            LocalDatabaseTest::class,
            ForeignDatabaseTest::class,
        ];
    }
}
