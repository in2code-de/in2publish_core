<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Anomaly;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Model\Task\FlushFileEdgeCacheTask;

use function array_keys;

class PublishedFileIdentifierCollector
{
    /** @var array<int, true> */
    protected $collectedRecords = [];

    /** @var TaskRepository */
    protected $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function registerPublishedFile(RecordInterface $record): void
    {
        $this->collectedRecords[$record->getIdentifier()] = true;
    }

    public function writeFlushFileEdgeCacheTask(): void
    {
        if (empty($this->collectedRecords)) {
            return;
        }
        $this->taskRepository->add(new FlushFileEdgeCacheTask(array_keys($this->collectedRecords)));
        $this->collectedRecords = [];
    }
}
