<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Cache\Exception;

use In2code\In2publish\In2publishException;
use Throwable;

class CacheableValueCanNotBeGeneratedException extends In2publishException
{
    /** @var mixed */
    private $value;

    /**
     * @param mixed $value
     */
    public function __construct($value, Throwable $previous = null)
    {
        $this->value = $value;
        parent::__construct('', 1698857094, $previous);
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
