<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Factory;

use In2code\In2publishCore\Component\Core\Record\Factory\Exception\MissingDatabaseRecordFactoryException;

use function krsort;

class DatabaseRecordFactoryFactory
{
    /**
     * @var array<int, array<int, DatabaseRecordFactory>>
     */
    private array $factories = [];

    public function addFactory(DatabaseRecordFactory $factory): void
    {
        $this->factories[$factory->getPriority()][] = $factory;
        krsort($this->factories);
    }

    public function createFactoryForTable(string $table): DatabaseRecordFactory
    {
        foreach ($this->factories as $factories) {
            foreach ($factories as $factory) {
                if ($factory->isResponsible($table)) {
                    return $factory;
                }
            }
        }
        throw new MissingDatabaseRecordFactoryException($table, 2448336290);
    }
}
