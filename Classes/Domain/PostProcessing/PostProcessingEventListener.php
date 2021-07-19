<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\PostProcessing;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\PostProcessing\Processor\PostProcessor;
use In2code\In2publishCore\Event\RecordInstanceWasInstantiated;

class PostProcessingEventListener
{
    private const TABLE_SYS_FILE = 'sys_file';

    /** @var PostProcessor */
    protected $postProcessor;

    /** @var RecordInterface[] */
    protected $registeredInstances = [];

    public function __construct(PostProcessor $postProcessor)
    {
        $this->postProcessor = $postProcessor;
    }

    public function onRecordInstanceWasInstantiated(RecordInstanceWasInstantiated $event): void
    {
        $record = $event->getRecord();
        if (
            self::TABLE_SYS_FILE === $record->getTableName()
            && ($record->localRecordExists() || $record->foreignRecordExists())
        ) {
            $this->registeredInstances[] = $record;
        }
    }

    public function onRootRecordCreationWasFinished(): void
    {
        if (empty($this->registeredInstances)) {
            return;
        }
        $records = $this->registeredInstances;
        $this->registeredInstances = [];
        $this->postProcessor->postProcess($records);
    }
}
