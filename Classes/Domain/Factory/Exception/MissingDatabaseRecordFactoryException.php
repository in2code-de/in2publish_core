<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Factory\Exception;

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function sprintf;

class MissingDatabaseRecordFactoryException extends In2publishCoreException
{
    private const MESSAGE = 'No factory found for table %s';
    public const CODE = 1657191754;
    private string $table;

    public function __construct(string $table, Throwable $previous = null)
    {
        $this->table = $table;
        parent::__construct(sprintf(self::MESSAGE, $table), self::CODE, $previous);
    }

    public function getTable(): string
    {
        return $this->table;
    }
}
