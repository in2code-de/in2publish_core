<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Reason;

use In2code\In2publishCore\Component\Core\Collection\FlatCollection;

/**
 * @method Reason[] getAll()
 */
class Reasons extends FlatCollection
{
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
        $this->objects[] = $reason;
    }
}
