<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

class StoragesForTestingWereFetched
{
    /** @var array */
    private $arguments;

    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
