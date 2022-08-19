<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RemoteProcedureCall\Exception;

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function sprintf;

class StorageIsOfflineException extends In2publishCoreException
{
    private const MESSAGE = 'The storage %s is offline';
    public const CODE = 1656411690;
    private int $storage;

    public function __construct(int $storage, Throwable $previous = null)
    {
        $this->storage = $storage;
        parent::__construct(sprintf(self::MESSAGE, $storage), self::CODE, $previous);
    }

    public function getStorage(): int
    {
        return $this->storage;
    }
}
