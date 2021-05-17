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

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SortingPublisher
 */
class SortingPublisher implements SingletonInterface
{
    /**
     * @var array
     */
    protected $sortingsToBePublished;

    /**
     * @var Connection
     */
    protected $localDatabase;

    /**
     * @var Connection
     */
    protected $foreignDatabase;

    /**
     * Constructor
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        $this->sortingsToBePublished = [];
    }

    public function collectSortingsToBePublished(
        string $tableName,
        RecordInterface $record,
        CommonRepository $commonRepository
    ) {
        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        if (!$tcaService->getNameOfSortingField($tableName)) {
            return false;
        }
        if ($this->tableContainsPid($tableName)) {
            $pid = $record->getLocalProperties()['pid'];
            // check if field sorting has changed
            $sortingField = $tcaService->getNameOfSortingField($tableName);
            if ($record->getLocalProperties()[$sortingField] !== $record->getForeignProperties()[$sortingField]) {
                if (array_key_exists($tableName, $this->sortingsToBePublished)) {
                    $tableArray = $this->sortingsToBePublished[$tableName];
                    // skip if sorting array contains $pid/$tableName
                    if (array_key_exists($pid, $tableArray)) {
                        return;
                    }
                    // add to sorting array if $pid is missing
                    $this->sortingsToBePublished[$tableName][$pid] = $pid;

                    return;
                }
                // add to sorting array if $tableName is missing
                $this->sortingsToBePublished[$tableName][$pid] = $pid;
            }
        }
    }

    protected function tableContainsPid(string $tableName): bool
    {
        $query = $this->localDatabase->createQueryBuilder();

        try {
            $constraint = $query->expr()->gt('pid', $query->createNamedParameter(0));
            $query->select('*')
                ->from($tableName)
                ->where($constraint);
            $query->execute();

            return true;
        } catch (InvalidFieldNameException $ex) {
            return false;
        }
    }

    public function publishSortingRecursively(
        RecordInterface $record,
        CommonRepository $commonRepository
    ): void {
        foreach ($this->sortingsToBePublished as $tableName => $pidList) {
            $uidArray = [];
            $uidList = $this->getRecordUidsForTableAndPids($tableName, $pidList);
            while ($uid = $uidList->fetch()) {
                $uidArray[] = $uid['uid'];
            }
            $this->publishSortingForUidList($tableName, $uidArray);
        }
    }

    protected function getRecordUidsForTableAndPids(string $tableName, array $pidList): Statement
    {
        $pidList = array_keys($pidList);
        $query = $this->localDatabase->createQueryBuilder();
        $constraint = $query->expr()->in('pid', $pidList);

        return $query->select('uid', 'sorting')
            ->from($tableName)
            ->where($constraint)
            ->execute();
    }

    protected function publishSortingForUidList(string $tableName, array $uidList): void
    {
        $localQuery = $this->localDatabase->createQueryBuilder();
        $foreignQuery = $this->foreignDatabase->createQueryBuilder();

        foreach ($uidList as $uid) {
            $uidConstraint = $localQuery->expr()->eq('uid', $uid);
            $statement = $localQuery
                ->select('uid', 'sorting')
                ->from($tableName)
                ->where($uidConstraint)
                ->setMaxResults(1)
                ->execute();

            while ($row = $statement->fetch()) {
                $sorting = $row['sorting'];
                $foreignQuery
                    ->update($tableName)
                    ->where($uidConstraint)
                    ->set('sorting', $sorting)
                    ->execute();
            }
        }
    }
}
