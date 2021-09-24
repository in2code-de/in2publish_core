<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

final class CreatedDefaultHelpLabels
{
    /** @var string[] */
    private $supports;

    public function __construct(array $supports)
    {
        $this->supports = $supports;
    }

    public function getSupports(): array
    {
        return $this->supports;
    }

    public function setSupports(array $supports): void
    {
        $this->supports = $supports;
    }

    public function addSupport(string $support): void
    {
        $this->supports[] = $support;
    }
}
