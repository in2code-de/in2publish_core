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

use Doctrine\DBAL\Result;
use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function in_array;

use const In2code\In2publishCore\TYPO3_V11;

class FileEdgeCacheInvalidationService
{
    use LocalDatabaseInjection;

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
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    protected function selectSysRefIndexRecords(array $uidList): Result
    {
        $query = $this->localDatabase->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('tablename as table', 'recuid as uid')->from('sys_refindex')->where(
            $query->expr()->and(
                $query->expr()->eq('ref_table', '"sys_file"'),
                $query->expr()->in('ref_uid', $uidList),
            ),
        );
        return $query->executeQuery();
    }

    /**
     * @param int[] $uidList
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidReturnType
     */
    protected function selectSysFileReferenceRecords(array $uidList): Result
    {
        $query = $this->localDatabase->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('tablenames as table', 'uid_foreign as uid')
              ->from('sys_file_reference')
              ->where($query->expr()->in('uid_local', $uidList));
        if (TYPO3_V11) {
            $query->andWhere($query->expr()->eq('table_local', '"sys_file"'));
        }
        return $query->executeQuery();
    }

    protected function addResultsToCollection(Result $statement, RecordCollection $recordCollection): void
    {
        while ($row = $statement->fetchAssociative()) {
            $table = $row['table'];
            $uid = (int)$row['uid'];
            $recordCollection->addRecord($table, $uid);
        }
    }

    protected function resolveRecordsToPages(RecordCollection $recordCollection): void
    {
        $schemaManager = $this->localDatabase->createSchemaManager();
        $tableNames = $schemaManager->listTableNames();

        foreach ($recordCollection->getRecords() as $table => $recordUidList) {
            if (
                $table === '_file'
                || $table === '_folder'
                || !in_array($table, $tableNames, true)
                || !array_key_exists('pid', $schemaManager->listTableColumns($table))
            ) {
                continue;
            }
            $query = $this->localDatabase->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('pid')->from($table);
            $query->where($query->expr()->in('uid', $recordUidList));
            $query->groupBy('pid');
            $statement = $query->executeQuery();
            while ($page = $statement->fetchOne()) {
                $recordCollection->addRecord('pages', $page);
            }
        }
    }

    protected function clearCachesForPages(RecordCollection $recordCollection): void
    {
        $dataHandler = $this->getDataHandler();
        $dataHandler->start([], []);
        $pages = $recordCollection->getPages();
        foreach ($pages as $page) {
            $dataHandler->clear_cacheCmd($page);
        }
    }

    protected function getDataHandler(): DataHandler
    {
        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        /** @var CommandLineUserAuthentication $user */
        $user = $GLOBALS['BE_USER'];

        if (!$user->user) {
            $user->authenticate();
        }

        $dataHandler->BE_USER = $user;

        /** @psalm-suppress InternalProperty */
        $dataHandler->admin = true;
        return $dataHandler;
    }
}
