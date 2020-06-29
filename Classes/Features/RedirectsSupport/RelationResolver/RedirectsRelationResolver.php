<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\RelationResolver;

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

use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Http\Message\UriInterface;

use function array_column;
use function array_keys;
use function array_merge;
use function array_unique;

/**
 * Class RedirectsRelationResolver
 */
class RedirectsRelationResolver
{
    protected $localDatabase;

    protected $foreignDatabase;

    protected $rtc = [];

    public function __construct()
    {
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
    }

    public function collectRedirectsByUriRecursive(UriInterface $uri, array $rows = []): array
    {
        $uriStr = (string)$uri;
        if (isset($this->rtc['seen_uris'][$uriStr])) {
            return $rows;
        }
        $this->rtc['seen_uris'][$uriStr] = true;

        $query = $this->localDatabase->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from('sys_redirect')
              ->where($query->expr()->eq('source_host', $query->createNamedParameter($uri->getHost())))
              ->andWhere($query->expr()->eq('target', $query->createNamedParameter($uri->getPath())));
        $statement = $query->execute();
        $localRows = $statement->fetchAll();
        $localRows = array_column($localRows, null, 'uid');
        $localUids = array_keys($localRows);

        $query = $this->foreignDatabase->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from('sys_redirect')
              ->where($query->expr()->eq('source_host', $query->createNamedParameter($uri->getHost())))
              ->andWhere($query->expr()->eq('target', $query->createNamedParameter($uri->getPath())));
        $statement = $query->execute();
        $foreignRows = $statement->fetchAll();
        $foreignRows = array_column($foreignRows, null, 'uid');
        $foreignUids = array_keys($localRows);

        $found = [];
        foreach (array_unique(array_merge($localUids, $foreignUids)) as $uid) {
            $localRow = $localRows[$uid] ?? null;
            $foreignRow = $foreignRows[$uid] ?? null;
            $rows[$uid] = [
                'local' => $localRow,
                'foreign' => $foreignRow,
            ];
            $found[$uid] = [
                'local' => $localRow,
                'foreign' => $foreignRow,
            ];
        }

        foreach ($found as $row) {
            $row = $row['local'] ?? $row['foreign'];
            $sourceHost = $row['source_host'];
            $sourcePath = $row['source_path'];
            if ('/' === $sourcePath) {
                continue;
            }
            $nextUri = $uri->withHost($sourceHost)->withPath($sourcePath);
            $rows = $this->collectRedirectsByUriRecursive($nextUri, $rows);
        }

        return $rows;
    }
}
