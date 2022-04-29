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

use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Service\Configuration\TcaService;
use TYPO3\CMS\Core\Database\Connection;

class SortingPublisher
{
    protected Connection $localDatabase;

    protected Connection $foreignDatabase;

    protected TcaService $tcaService;

    /** @var array<string, array<int, int>> */
    protected array $sortingsToBePublished = [];

    public function __construct(Connection $localDatabase, Connection $foreignDatabase, TcaService $tcaService)
    {
        $this->localDatabase = $localDatabase;
        $this->foreignDatabase = $foreignDatabase;
        $this->tcaService = $tcaService;
    }

    public function collectSortingsToBePublished(PublishingOfOneRecordBegan $event): void
    {
        $record = $event->getRecord();
        if (!$record->hasLocalProperty('pid')) {
            return;
        }
        $pid = $record->getLocalProperty('pid');
        $tableName = $record->getTableName();
        if (isset($this->sortingsToBePublished[$tableName][$pid])) {
            return;
        }

        $sortingField = $this->tcaService->getNameOfSortingField($tableName);

        if (empty($sortingField)) {
            return;
        }

        // check if field sorting has changed
        if ($record->getLocalProperty($sortingField) !== $record->getForeignProperty($sortingField)) {
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
            $localRows = $statement->fetchAll();

            $updates = [];
            foreach ($localRows as $localRow) {
                $updates[$localRow['sorting']][] = $localRow['uid'];
            }

            foreach ($updates as $sorting => $uidList) {
                $sortingField = $this->tcaService->getNameOfSortingField($tableName);

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
