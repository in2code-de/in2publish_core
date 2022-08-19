<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Collection;

use ArrayIterator;
use Closure;
use Iterator;
use IteratorAggregate;

use function array_map;
use function array_reduce;

abstract class FlatCollection implements IteratorAggregate
{
    protected array $objects = [];

    public function getAll(): array
    {
        return $this->objects;
    }

    public function isEmpty(): bool
    {
        return empty($this->objects);
    }

    public function map(Closure $closure): array
    {
        return array_map($closure, $this->objects);
    }

    /**
     * @param mixed $initial
     * @return mixed
     */
    public function reduce(Closure $closure, $initial = null)
    {
        return array_reduce($this->objects, $closure, $initial);
    }

    public function are(Closure $closure): bool
    {
        foreach ($this->objects as $object) {
            if (!$closure($object)) {
                return false;
            }
        }
        return true;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->objects);
    }
}
