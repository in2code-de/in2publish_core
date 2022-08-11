<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\PublishSorting\Domain\Anomaly;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
 * Christine Zoglmeier <christine.zoglmeier@in2code.de>
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

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use TYPO3\CMS\Core\Database\Connection;

class SortingPublisher
{
    use LocalDatabaseInjection;

    protected Connection $foreignDatabase;
    /** @var array<string, array<int, int>> */
    protected array $sortingsToBePublished = [];

    public function injectForeignDatabase(Connection $foreignDatabase): void
    {
        $this->foreignDatabase = $foreignDatabase;
    }

    public function collectSortingsToBePublished(PublishingOfOneRecordBegan $event): void
    {
        $record = $event->getRecord();
        $localProps = $record->getLocalProps();
        if (!array_key_exists('pid', $localProps)) {
            return;
        }
        $pid = $localProps['pid'];
        $tableName = $record->getClassification();
        if (isset($this->sortingsToBePublished[$tableName][$pid])) {
            return;
        }

        $sortingField = $GLOBALS['TCA'][$tableName]['ctrl']['sortby'] ?? null;

        if (empty($sortingField)) {
            return;
        }

        // check if field sorting has changed
        $foreignProps = $record->getForeignProps();
        if (($localProps[$sortingField] ?? null) !== ($foreignProps[$sortingField] ?? null)) {
            $this->sortingsToBePublished[$tableName][$pid] = $pid;
        }
    }

    public function publishSortingRecursively(): void
    {
        foreach ($this->sortingsToBePublished as $tableName => $pidList) {
            $query = $this->localDatabase->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('uid', 'sorting')
                  ->from($tableName)
                  ->where($query->expr()->in('pid', $pidList));
            $statement = $query->execute();
            $localRows = $statement->fetchAllAssociative();

            $updates = [];
            foreach ($localRows as $localRow) {
                $updates[$localRow['sorting']][] = $localRow['uid'];
            }

            foreach ($updates as $sorting => $uidList) {
                $sortingField = $GLOBALS['TCA'][$tableName]['ctrl']['sortby'];

                $updateQuery = $this->foreignDatabase->createQueryBuilder();
                $updateQuery->getRestrictions()->removeAll();
                $updateQuery->update($tableName)
                            ->set($sortingField, $sorting)
                            ->where($updateQuery->expr()->in('uid', $uidList))
                            ->execute();
            }
        }
        $this->sortingsToBePublished = [];
    }
}
