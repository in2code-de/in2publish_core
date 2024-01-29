<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\CompareDatabaseTool\Controller;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\CommonInjection\ForeignDatabaseInjection;
use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Features\AdminTools\Controller\Traits\AdminToolsModuleTemplate;
use In2code\In2publishCore\Features\CompareDatabaseTool\Domain\DTO\ComparisonRequest;
use In2code\In2publishCore\Service\Configuration\IgnoredFieldsServiceInjection;
use In2code\In2publishCore\Utility\ArrayUtility;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_column;
use function array_combine;
use function array_diff;
use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function max;

class CompareDatabaseToolController extends ActionController
{
    use AdminToolsModuleTemplate;
    use LocalDatabaseInjection;
    use ForeignDatabaseInjection;
    use IgnoredFieldsServiceInjection;

    public function indexAction(): ResponseInterface
    {
        $tables = $this->getAllNonExcludedTables();
        $tables = array_intersect($tables, array_keys($GLOBALS['TCA']));
        $this->view->assign('tables', array_combine($tables, $tables));
        return $this->htmlResponse();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) PR welcome
     * @SuppressWarnings(PHPMD.NPathComplexity) PR welcome
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) PR welcome
     */
    public function compareAction(ComparisonRequest $comparisonRequest = null): ResponseInterface
    {
        if (null === $comparisonRequest) {
            $this->redirect('index');
        }
        $allowedTables = $this->getAllNonExcludedTables();
        $requestedTables = $comparisonRequest->getTables();

        $tables = array_intersect($allowedTables, $requestedTables, array_keys($GLOBALS['TCA']));

        $differences = [];

        foreach ($tables as $table) {
            $tableIdentifier = $this->localDatabase->quoteIdentifier($table);
            $localResult = $this->localDatabase->executeQuery("SELECT MAX(uid) from $tableIdentifier")
                                               ->fetchOne();
            $tableIdentifier = $this->foreignDatabase->quoteIdentifier($table);
            $foreignResult = $this->foreignDatabase->executeQuery("SELECT MAX(uid) from $tableIdentifier")
                                                   ->fetchOne();

            if (null === $localResult && null === $foreignResult) {
                continue;
            }
            if (null === $localResult && $foreignResult > 0) {
                $differences[$table]['general'] = 'local_empty';
                continue;
            }
            if ($localResult > 0 && null === $foreignResult) {
                $differences[$table]['general'] = 'foreign_empty';
                continue;
            }

            $maxUid = max($localResult, $foreignResult);

            $rest = $maxUid % 1000;
            $iterations = $maxUid / 1000;
            if ($rest > 0) {
                ++$iterations;
            }

            for ($i = 0; $i < $iterations; $i++) {
                $offset = 1000 * $i;
                $limit = 1000 * ($i + 1);

                $localQuery = $this->localDatabase->createQueryBuilder();
                $localResult = $localQuery->select('*')
                                          ->from($table)
                                          ->where(
                                              $localQuery->expr()->and(
                                                  $localQuery->expr()->gte('uid', $offset),
                                                  $localQuery->expr()->lt('uid', $limit),
                                              ),
                                          )
                                          ->executeQuery();
                $localRows = array_column($localResult->fetchAllAssociative(), null, 'uid');
                $foreignQuery = $this->foreignDatabase->createQueryBuilder();
                $foreignResult = $foreignQuery->select('*')
                                              ->from($table)
                                              ->where(
                                                  $foreignQuery->expr()->and(
                                                      $foreignQuery->expr()->gte('uid', $offset),
                                                      $foreignQuery->expr()->lt('uid', $limit),
                                                  ),
                                              )
                                              ->executeQuery();
                $foreignRows = array_column($foreignResult->fetchAllAssociative(), null, 'uid');

                $uidList = array_unique(array_merge(array_keys($localRows), array_keys($foreignRows)));

                foreach ($uidList as $uid) {
                    $localRowExists = array_key_exists($uid, $localRows);
                    $foreignRowExists = array_key_exists($uid, $foreignRows);
                    if ($localRowExists && $foreignRowExists) {
                        $ignoredFields = $this->ignoredFieldsService->getIgnoredFields($table);

                        $localRow = $localRows[$uid];
                        $foreignRow = $foreignRows[$uid];
                        $localRowCleaned = ArrayUtility::removeFromArrayByKey($localRow, $ignoredFields);
                        $foreignRowCleaned = ArrayUtility::removeFromArrayByKey($foreignRow, $ignoredFields);

                        $diff = array_diff($localRowCleaned, $foreignRowCleaned);
                        if (!empty($diff)) {
                            $differences[$table]['diff'][] = [
                                'local' => $localRow,
                                'foreign' => $foreignRow,
                                'diff' => $diff,
                            ];
                        }
                    } elseif ($localRowExists && !$foreignRowExists) {
                        $differences[$table]['only_local'][] = $localRows[$uid];
                    } elseif ($foreignRowExists) {
                        $differences[$table]['only_foreign'][] = $foreignRows[$uid];
                    }
                }
            }
        }
        foreach ($differences as $table => $places) {
            foreach ($places as $place => $values) {
                $differences[$table][$place] = $values;
            }
        }
        $this->view->assign('differences', $differences);
        return $this->htmlResponse();
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) PR welcome
     * @SuppressWarnings(PHPMD.NPathComplexity) PR welcome
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) PR welcome
     */
    public function transferAction(string $table, int $uid, string $expected): void
    {
        $localQuery = $this->localDatabase->createQueryBuilder();
        $localQuery->getRestrictions()->removeAll();
        $localQuery->select('*')
                   ->from($table)
                   ->where($localQuery->expr()->eq('uid', $localQuery->createNamedParameter($uid)))
                   ->setMaxResults(1);
        $localResult = $localQuery->executeQuery();
        $localRow = $localResult->fetchAssociative();

        $foreignQuery = $this->foreignDatabase->createQueryBuilder();
        $foreignQuery->getRestrictions()->removeAll();
        $foreignQuery->select('*')
                     ->from($table)
                     ->where($foreignQuery->expr()->eq('uid', $foreignQuery->createNamedParameter($uid)))
                     ->setMaxResults(1);
        $foreignResult = $foreignQuery->executeQuery();
        $foreignRow = $foreignResult->fetchAssociative();

        if (empty($localRow) && empty($foreignRow)) {
            $this->addFlashMessage(
                LocalizationUtility::translate('compare_database.transfer.record_missing', 'in2publish_core'),
                LocalizationUtility::translate('compare_database.transfer.error', 'in2publish_core'),
                AbstractMessage::ERROR,
            );
            $this->redirect('index');
        }

        if ($expected === 'only_foreign') {
            if (!(empty($localRow) && !empty($foreignRow))) {
                $this->addFlashMessage(
                    LocalizationUtility::translate('compare_database.transfer.exists_on_foreign', 'in2publish_core'),
                    LocalizationUtility::translate('compare_database.transfer.error', 'in2publish_core'),
                    AbstractMessage::ERROR,
                );
                $this->redirect('index');
            }
            $foreignQuery = $this->foreignDatabase->createQueryBuilder();
            $foreignQuery->delete($table)
                         ->where($localQuery->expr()->eq('uid', $foreignQuery->createNamedParameter($uid)));
            $foreignResult = $foreignQuery->executeStatement();
            if (1 === $foreignResult) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'compare_database.transfer.deleted_from_foreign',
                        'in2publish_core',
                        [$table, $uid],
                    ),
                    LocalizationUtility::translate('compare_database.transfer.success', 'in2publish_core'),
                );
            }
        }

        if ($expected === 'only_local') {
            if (!(!empty($localRow) && empty($foreignRow))) {
                $this->addFlashMessage(
                    LocalizationUtility::translate('compare_database.transfer.exists_on_local', 'in2publish_core'),
                    LocalizationUtility::translate('compare_database.transfer.error', 'in2publish_core'),
                    AbstractMessage::ERROR,
                );
                $this->redirect('index');
            }
            $foreignQuery = $this->foreignDatabase->createQueryBuilder();
            $foreignQuery->insert($table)
                         ->values($localRow);
            $foreignResult = $foreignQuery->executeStatement();
            if (1 === $foreignResult) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'compare_database.transfer.transferred_to_foreign',
                        'in2publish_core',
                        [$table, $uid],
                    ),
                    LocalizationUtility::translate('compare_database.transfer.success', 'in2publish_core'),
                );
            }
        }

        if ($expected === 'diff') {
            if (!(!empty($localRow) && !empty($foreignRow))) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'compare_database.transfer.does_not_exists_on_both',
                        'in2publish_core',
                    ),
                    LocalizationUtility::translate('compare_database.transfer.error', 'in2publish_core'),
                    AbstractMessage::ERROR,
                );
                $this->redirect('index');
            }
            $foreignQuery = $this->foreignDatabase->createQueryBuilder();
            $foreignQuery->update($table);
            foreach ($localRow as $field => $value) {
                if ($foreignRow[$field] !== $value) {
                    $foreignQuery->set($field, $value);
                }
            }
            $foreignQuery->where($foreignQuery->expr()->eq('uid', $foreignQuery->createNamedParameter($uid)));
            $foreignResult = $foreignQuery->executeStatement();
            if (1 === $foreignResult) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'compare_database.transfer.updated_on_foreign',
                        'in2publish_core',
                        [$table, $uid],
                    ),
                    LocalizationUtility::translate('compare_database.transfer.success', 'in2publish_core'),
                );
            }
        }

        $this->redirect('index');
    }

    protected function getAllNonExcludedTables(): array
    {
        $tables = $this->localDatabase->createSchemaManager()->listTableNames();
        $excludedTables = $this->configContainer->get('excludeRelatedTables');
        return array_diff($tables, $excludedTables);
    }
}
