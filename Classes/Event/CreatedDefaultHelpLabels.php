<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

final class CreatedDefaultHelpLabels
{
    /** @var array */
    private $supports;

    public function __construct(array $supports)
    {
        $this->supports = $supports;
    }

    public function getSupports(): array
    {
        return $this->supports;
    }

    public function setSupports(array $supports): self
    {
        $this->supports = $supports;

        return $this;
    }
    public function addSupport(string $support) {
        $this->supports[] = $support;
    }
}
