<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Service;

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

use Doctrine\DBAL\Driver\ResultStatement;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function in_array;

class FileEdgeCacheInvalidationService
{
    /** @var null|Connection */
    protected $connection;

    public function __construct()
    {
        $this->connection = DatabaseUtility::buildLocalDatabaseConnection();
    }

    public function flushCachesForFiles(array $uidList): void
    {
        $recordCollection = $this->resolveEdgePagesToClearCachesFor($uidList);

        if (!$recordCollection->hasPages()) {
            return;
        }

        $this->clearCachesForPages($recordCollection);
    }

    protected function resolveEdgePagesToClearCachesFor(array $uidList): RecordCollection
    {
        $recordCollection = new RecordCollection();

        $statement = $this->selectSysRefIndexRecords($uidList);
        $this->addResultsToCollection($statement, $recordCollection);

        $statement = $this->selectSysFileReferenceRecords($uidList);
        $this->addResultsToCollection($statement, $recordCollection);

        $this->resolveRecordsToPages($recordCollection);

        return $recordCollection;
    }

    /**
     * @param int[] $uidList
     * @return ResultStatement
     */
    protected function selectSysRefIndexRecords(array $uidList): ResultStatement
    {
        $query = $this->connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('tablename as table', 'recuid as uid')->from('sys_refindex')->where(
            $query->expr()->andX(
                $query->expr()->eq('ref_table', '"sys_file"'),
                $query->expr()->in('ref_uid', $uidList)
            )
        );
        return $query->execute();
    }

    /**
     * @param int[] $uidList
     * @return ResultStatement
     */
    protected function selectSysFileReferenceRecords(array $uidList): ResultStatement
    {
        $query = $this->connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('tablenames as table', 'uid_foreign as uid')
              ->from('sys_file_reference')
              ->where(
                  $query->expr()->andX(
                      $query->expr()->eq('table_local', '"sys_file"'),
                      $query->expr()->in('uid_local', $uidList)
                  )
              );
        return $query->execute();
    }

    protected function addResultsToCollection(ResultStatement $statement, RecordCollection $recordCollection): void
    {
        while ($row = $statement->fetchAssociative()) {
            $table = $row['table'];
            $uid = (int)$row['uid'];
            $recordCollection->addRecord($table, $uid);
        }
    }

    protected function resolveRecordsToPages(RecordCollection $recordCollection): void
    {
        $schemaManager = $this->connection->getSchemaManager();
        $tableNames = $schemaManager->listTableNames();

        foreach ($recordCollection->getRecords() as $table => $recordUidList) {
            if (!in_array($table, $tableNames) || !array_key_exists('pid', $schemaManager->listTableColumns($table))) {
                continue;
            }
            $query = $this->connection->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('pid')->from($table);
            $query->where($query->expr()->in('uid', $recordUidList));
            $query->groupBy('pid');
            $statement = $query->execute();
            while ($page = $statement->fetchOne()) {
                $recordCollection->addRecord('pages', $page);
            }
        }
    }

    protected function clearCachesForPages(RecordCollection $recordCollection): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], []);
        $pages = $recordCollection->getPages();
        foreach ($pages as $page) {
            $dataHandler->clear_cacheCmd($page);
        }
    }
}
