<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Repository\Exception;

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function sprintf;

class MissingArgumentException extends In2publishCoreException
{
    protected const MESSAGE = 'The argument "%s" is required.';
    public const CODE = 1631016625;

    /** @var string */
    protected $argumentName;

    public function __construct(string $argumentName, Throwable $previous = null)
    {
        $this->argumentName = $argumentName;
        parent::__construct(sprintf(self::MESSAGE, $argumentName), self::CODE, $previous);
    }

    public function getArgumentName(): string
    {
        return $this->argumentName;
    }
}
