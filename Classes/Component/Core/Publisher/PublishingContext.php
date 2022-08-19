<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;

class PublishingContext
{
    protected RecordTree $recordTree;

    public function __construct(RecordTree $recordTree)
    {
        $this->recordTree = $recordTree;
    }

    public function getRecordTree(): RecordTree
    {
        return $this->recordTree;
    }
}
