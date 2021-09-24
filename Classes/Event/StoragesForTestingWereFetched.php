<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

class StoragesForTestingWereFetched
{
    /** @var array */
    private $localStorages;

    /** @var array */
    private $foreignStorages;

    /** @var string */
    private $purpose;

    public function __construct(array $localStorages, array $foreignStorages, string $purpose)
    {
        $this->localStorages = $localStorages;
        $this->foreignStorages = $foreignStorages;
        $this->purpose = $purpose;
    }

    public function getLocalStorages(): array
    {
        return $this->localStorages;
    }

    public function setLocalStorages(array $localStorages): void
    {
        $this->localStorages = $localStorages;
    }

    public function getForeignStorages(): array
    {
        return $this->foreignStorages;
    }

    public function setForeignStorages(array $foreignStorages): void
    {
        $this->foreignStorages = $foreignStorages;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }
}
