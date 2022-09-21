<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;

/**
 * @codeCoverageIgnore
 */
final class RecordRelationsWereResolved
{
    private RecordTree $recordTree;

    public function __construct(RecordTree $recordTree)
    {
        $this->recordTree = $recordTree;
    }

    public function getRecordTree(): RecordTree
    {
        return $this->recordTree;
    }
}
