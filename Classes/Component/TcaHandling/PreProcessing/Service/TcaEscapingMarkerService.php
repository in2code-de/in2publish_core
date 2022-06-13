<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service;

use TYPO3\CMS\Core\Database\Connection;

use function preg_replace_callback;

class TcaEscapingMarkerService
{
    protected Connection $localDatabase;

    public function __construct(Connection $localDatabase)
    {
        $this->localDatabase = $localDatabase;
    }

    public function escapeMarkedIdentifier(string $sql): string
    {
        if (str_contains($sql, '{#')) {
            $sql = preg_replace_callback(
                '/{#(?P<identifier>[^}]+)}/',
                fn(array $matches) => $this->localDatabase->quoteIdentifier($matches['identifier']),
                $sql
            );
        }

        return $sql;
    }
}
