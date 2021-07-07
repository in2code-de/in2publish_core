<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Controller\RecordController;
use In2code\In2publishCore\Domain\Model\RecordInterface;

final class RecordWasSelectedForPublishing
{
    /** @var RecordInterface */
    private $record;

    /** @var RecordController */
    private $recordController;

    public function __construct(RecordInterface $record, RecordController $recordController)
    {
        $this->record = $record;
        $this->recordController = $recordController;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    public function getRecordController(): RecordController
    {
        return $this->recordController;
    }
}
