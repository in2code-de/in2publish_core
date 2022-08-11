<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\Service;

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;

use function preg_replace_callback;

class TcaEscapingMarkerService
{
    use LocalDatabaseInjection;

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
