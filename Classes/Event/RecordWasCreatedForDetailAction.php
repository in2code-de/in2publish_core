<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Controller\RecordController;
use In2code\In2publishCore\Domain\Model\RecordInterface;

final class RecordWasCreatedForDetailAction
{
    /** @var RecordController */
    private $recordController;

    /** @var RecordInterface */
    private $record;

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
