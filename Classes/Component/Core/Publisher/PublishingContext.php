<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;

class PublishingContext
{
    protected RecordTree $recordTree;
    public bool $publishChildPages = false;

    public function __construct(
        RecordTree $recordTree,
        bool $publishChildPages = null
    ) {
        $this->recordTree = $recordTree;
        if (null !== $publishChildPages) {
            $this->publishChildPages = $publishChildPages;
        }
    }

    public function getRecordTree(): RecordTree
    {
        return $this->recordTree;
    }
}
