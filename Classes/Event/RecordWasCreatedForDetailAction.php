<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Controller\RecordController;
use In2code\In2publishCore\Domain\Model\RecordInterface;

class RecordWasCreatedForDetailAction
{
    /** @var RecordController */
    protected $recordController;

    /** @var RecordInterface */
    protected $record;

    public function __construct(RecordController $recordController, RecordInterface $record)
    {
        $this->recordController = $recordController;
        $this->record = $record;
    }

    public function getRecordController(): RecordController
    {
        return $this->recordController;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }
}
