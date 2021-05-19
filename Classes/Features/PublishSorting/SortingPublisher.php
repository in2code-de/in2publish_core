<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\PublishSorting;

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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SortingPublisher implements SingletonInterface
{
    /**
     * @var Connection
     */
    protected $localDatabase;

    /**
     * @var Connection
     */
    protected $foreignDatabase;

    /**
     * @var TcaService
     */
    protected $tcaService;

    /**
     * @var array
     */
    protected $sortingsToBePublished = [];

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);
    }

    public function collectSortingsToBePublished(
        string $tableName,
        RecordInterface $record,
        CommonRepository $commonRepository
    ): void {
        if (!$record->hasLocalProperty('pid')) {
            return;
        }
        $pid = $record->getLocalProperty('pid');
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

    public function publishSortingRecursively(
        RecordInterface $record,
        CommonRepository $commonRepository
    ): void {
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
    }
}
