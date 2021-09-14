<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Factory\Exception;

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

class ConnectionUnavailableException extends In2publishCoreException
{
    protected const MESSAGE = 'The connection for side "%s" is not available.';
    public const CODE = 1631623822;

    /** @var string */
    private $side;

    public function __construct(string $side, Throwable $previous = null)
    {
        $this->side = $side;
        parent::__construct(sprintf(self::MESSAGE, $side), self::CODE, $previous);
    }

    public function getSide(): string
    {
        return $this->side;
    }
}
