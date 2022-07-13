<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Reason;

use ArrayIterator;
use Iterator;
use IteratorAggregate;

class Reasons implements IteratorAggregate
{
    private array $reasons = [];

    public function __construct(iterable $reasons = [])
    {
        $this->addReasons($reasons);
    }

    public function addReasons(iterable $reasons): void
    {
        foreach ($reasons as $reason) {
            $this->addReason($reason);
        }
    }

    public function addReason(Reason $reason): void
    {
        $this->reasons[] = $reason;
    }

    /**
     * @return array<Reason>
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->reasons);
    }

    public function isEmpty(): bool
    {
        return empty($this->reasons);
    }
}
