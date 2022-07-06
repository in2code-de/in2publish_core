<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FullTablePublishing\Service;

use TYPO3\CMS\Core\Database\Connection;

class TableTransferService
{
    public function copyTableContents(Connection $source, Connection $target, string $table): void
    {
        $target->truncate($table);

        $query = $source->createQueryBuilder();
        $query->select('*')->from($table);
        $result = $query->execute();
        while ($row = $result->fetchAssociative()) {
            $target->insert($table, $row);
        }
    }
}
