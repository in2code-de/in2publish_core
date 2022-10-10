<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\RecordHandling\RecordFinder;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Repository\SysRedirectRepository;
use In2code\In2publishCore\Utility\BackendUtility;
use PDO;
use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function array_keys;

class PageRecordRedirectEnhancer
{
    /** @var RecordFinder */
    protected $recordFinder;

    /** @var Connection */
    protected $localDatabase;

    /** @var Connection */
    protected $foreignDatabase;

    /** @var SysRedirectRepository */
    protected $repo;

    /** @var LinkService */
    protected $linkService;

    /** @var array<string, array<int>> */
    protected $looseRedirects = [];

    public function __construct(
        RecordFinder $recordFinder,
        Connection $localDatabase,
        Connection $foreignDatabase,
        SysRedirectRepository $repo,
        LinkService $linkService
    ) {
        $this->recordFinder = $recordFinder;
        $this->localDatabase = $localDatabase;
        $this->foreignDatabase = $foreignDatabase;
        $this->repo = $repo;
        $this->linkService = $linkService;
    }

    public function addRedirectsToPageRecord(AllRelatedRecordsWereAddedToOneRecord $event): void
    {
        $record = $event->getRecord();
        if ('pages' !== $record->getTableName() || ($pid = $record->getIdentifier()) < 1) {
            return;
        }

        // Find associated sys_redirects
        $relatedRedirects = $this->recordFinder->findRecordsByProperties(
            [
                'tx_in2publishcore_page_uid' => $pid,
                'tx_in2publishcore_foreign_site_id' => null,
            ],
            'sys_redirect'
        );
        $record->addRelatedRecords($relatedRedirects);

        $this->run($record);
    }

    public function run(RecordInterface $record): void
    {
        $redirects = $this->findRedirectsByDynamicTarget($record);
        $redirects = $this->findMissingRowsByUid($redirects);
        $this->createAndAddRecordsToRecord($record, $redirects);

        $redirects = $this->findRedirectsByUri($record);
        $redirects = $this->findMissingRowsByUid($redirects);
        $this->createAndAddRecordsToRecord($record, $redirects);
        $this->processLooseRedirects($record);
    }

    /**
     * @return array<array{local?: mixed, foreign?: mixed}>
     */
    protected function findRedirectsByDynamicTarget(RecordInterface $record): array
    {
        $collected = [];

        $target = $this->linkService->asString([
            'type' => 'page',
            'pageuid' => $record->getIdentifier(),
            'parameters' => '_language=' . $record->getRecordLanguage(),
        ]);

        $except = [];
        $relatedRedirects = $record->getRelatedRecords()['sys_redirect'] ?? [];
        foreach ($relatedRedirects as $relatedRedirect) {
            $except[] = $relatedRedirect->getIdentifier();
        }

        $rows = $this->repo->findByRawTarget($this->localDatabase, $target, $except);
        foreach ($rows as $row) {
            $collected[$row['uid']]['local'] = $row;
        }
        $rows = $this->repo->findByRawTarget($this->foreignDatabase, $target, $except);
        foreach ($rows as $row) {
            $collected[$row['uid']]['foreign'] = $row;
        }
        return $collected;
    }

    /**
     * @param array<Uri> $uris
     * @param array<int, array{local?: array, foreign?: array}> $collected
     */
    protected function collectRedirectsByUri(array $uris, array $collected, string $side, Connection $connection): array
    {
        $newRows = $this->repo->findRawByUris($connection, $uris, array_keys($collected));

        if (empty($newRows)) {
            return $collected;
        }

        $newUris = [];
        foreach ($newRows as $row) {
            $collected[$row['uid']][$side] = $row;
            $newUris[$row['uid']] = $this->rowToUri($row);
        }

        return $this->collectRedirectsByUri($newUris, $collected, $side, $connection);
    }

    protected function collectLooseRedirects(?array $row, string $side): void
    {
        if (
            null !== $row
            && null === $row['tx_in2publishcore_page_uid']
            && null === $row['tx_in2publishcore_foreign_site_id']
        ) {
            $this->looseRedirects[$side][] = $row['uid'];
        }
    }

    protected function processLooseRedirects(RecordInterface $record): void
    {
        $pid = $record->getIdentifier();
        if (!empty($this->looseRedirects['local'])) {
            $this->assignRedirects($this->localDatabase, $this->looseRedirects['local'], $pid);
            $this->looseRedirects['local'] = [];
        }
        if (!empty($this->looseRedirects['foreign'])) {
            $this->assignRedirects($this->foreignDatabase, $this->looseRedirects['foreign'], $pid);
            $this->looseRedirects['foreign'] = [];
        }
    }

    protected function assignRedirects(Connection $connection, array $uids, int $pid): void
    {
        $query = $connection->createQueryBuilder();
        $query->update('sys_redirect')
              ->set('tx_in2publishcore_page_uid', $pid, true, PDO::PARAM_INT)
              ->where($query->expr()->in('uid', $uids))
              ->execute();
    }

    /**
     * @param array<int, array{local?: array, foreign?: array}> $redirects
     */
    protected function findMissingRowsByUid(array $redirects): array
    {
        $missingRows = ['local' => [], 'foreign' => []];

        foreach ($redirects as $uid => $sides) {
            $localFound = array_key_exists('local', $sides);
            $foreignFound = array_key_exists('foreign', $sides);
            if ($localFound && !$foreignFound) {
                $missingRows['foreign'][] = $uid;
            }
            if (!$localFound && $foreignFound) {
                $missingRows['local'][] = $uid;
            }
        }
        if (!empty($missingRows['local'])) {
            $rows = $this->repo->findRawByUids($this->localDatabase, $missingRows['local']);
            foreach ($rows as $row) {
                $redirects[$row['uid']]['local'] = $row;
            }
        }
        if (!empty($missingRows['foreign'])) {
            $rows = $this->repo->findRawByUids($this->foreignDatabase, $missingRows['foreign']);
            foreach ($rows as $row) {
                $redirects[$row['uid']]['foreign'] = $row;
            }
        }
        return $redirects;
    }

    protected function findRedirectsByUri(RecordInterface $record): array
    {
        $basicUris = [];

        $redirects = $this->getExistingRedirects($record);
        foreach ($redirects as $redirect) {
            if (!empty($redirect['local'])) {
                $basicUris[] = $this->rowToUri($redirect['local']);
            }
            if (!empty($redirect['foreign'])) {
                $basicUris[] = $this->rowToUri($redirect['foreign']);
            }
        }

        $pid = $record->getIdentifier();
        if ($record->localRecordExists()) {
            try {
                $uri = BackendUtility::buildPreviewUri('pages', $pid, 'local');
                if (null !== $uri) {
                    $uris = $basicUris;
                    $uris[] = $uri;
                    $redirects = $this->collectRedirectsByUri($uris, $redirects, 'local', $this->localDatabase);
                }
            } catch (Throwable $exception) {
                // no-op
            }
        }

        if ($record->foreignRecordExists()) {
            try {
                $uri = BackendUtility::buildPreviewUri('pages', $pid, 'foreign');
                if (null !== $uri) {
                    $uris = $basicUris;
                    $uris[] = $uri;
                    $redirects = $this->collectRedirectsByUri($uris, $redirects, 'foreign', $this->foreignDatabase);
                }
            } catch (Throwable $exception) {
                // no-op
            }
        }
        return $redirects;
    }

    protected function createAndAddRecordsToRecord(RecordInterface $record, array $redirects): void
    {
        $relatedRedirects = $record->getRelatedRecords()['sys_redirect'] ?? [];
        foreach ($redirects as $uid => $rowSet) {
            if (array_key_exists($uid, $relatedRedirects)) {
                continue;
            }
            foreach (['local', 'foreign'] as $side) {
                $this->collectLooseRedirects($rowSet[$side] ?? null, $side);
            }
            $relatedRedirect = GeneralUtility::makeInstance(
                Record::class,
                'sys_redirect',
                $rowSet['local'] ?? [],
                $rowSet['foreign'] ?? [],
                [],
                []
            );
            $record->addRelatedRecord($relatedRedirect);
        }
    }

    /**
     * @return array<int, array{local?: array, foreign?: array}>
     */
    protected function getExistingRedirects(RecordInterface $record): array
    {
        $redirects = [];
        $existingRedirects = $record->getRelatedRecords()['sys_redirect'] ?? [];
        foreach ($existingRedirects as $redirectRecord) {
            $row = [];
            if ($redirectRecord->localRecordExists()) {
                $row['local'] = $redirectRecord->getLocalProperties();
            }
            if ($redirectRecord->foreignRecordExists()) {
                $row['local'] = $redirectRecord->getForeignProperties();
            }
            $redirects[$redirectRecord->getIdentifier()] = $row;
        }
        return $redirects;
    }

    /**
     * @param array{source_path?: string, source_host?: string} $row
     * @noinspection PhpRedundantDocCommentInspection
     * @noinspection RedundantSuppression
     */
    protected function rowToUri(array $row): Uri
    {
        $uri = new Uri($row['source_path'] ?? '');
        if (!empty($row['source_host'])) {
            $uri = $uri->withHost($row['source_host']);
        }
        return $uri;
    }
}
