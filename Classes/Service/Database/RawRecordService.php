<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Database;

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

use In2code\In2publishCore\Component\Core\RecordIndex;
use In2code\In2publishCore\In2publishCoreException;
use PDO;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\SingletonInterface;

use function is_array;
use function sprintf;

class RawRecordService implements SingletonInterface
{
    /** @var array<string, Connection> */
    protected array $databases;
    protected array $cache = [];
    protected RecordIndex $recordIndex;

    public function __construct(Connection $localDatabase, Connection $foreignDatabase, RecordIndex $recordIndex)
    {
        $this->databases = [
            'local' => $localDatabase,
            'foreign' => $foreignDatabase,
        ];
        $this->recordIndex = $recordIndex;
    }

    public function getRawRecord(string $table, int $uid, string $side): ?array
    {
        if ('pages' === $table && 0 === $uid) {
            return null;
        }
        $record = $this->recordIndex->getRecord($table, $uid);
        if (null !== $record) {
            if ('local' === $side) {
                return $record->getLocalProps() ?: null;
            }
            if ('foreign' === $side) {
                return $record->getForeignProps() ?: null;
            }
        }

        if (empty($this->cache[$side][$table][$uid])) {
            $this->cache[$side][$table][$uid] = $this->fetchRecord($table, $uid, $side);
        }
        return $this->cache[$side][$table][$uid];
    }

    protected function fetchRecord(string $table, int $uid, string $side): ?array
    {
        $database = $this->databases[$side] ?? null;
        if (null === $database) {
            throw new In2publishCoreException(
                sprintf('Invalid side "%s" or database is not available', $side),
                1631722413
            );
        }
        $query = $database->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('*')
              ->from($table)
              ->where($query->expr()->eq('uid', $query->createNamedParameter($uid, PDO::PARAM_INT)))
              ->setMaxResults(1);
        $statement = $query->execute();
        $result = $statement->fetchAssociative();
        return is_array($result) ? $result : null;
    }
}
