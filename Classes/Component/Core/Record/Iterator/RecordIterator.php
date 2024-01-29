<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Iterator;

use Closure;
use In2code\In2publishCore\Component\Core\Record\Iterator\IterationControls\SkipChildren;
use In2code\In2publishCore\Component\Core\Record\Iterator\IterationControls\StopIteration;
use In2code\In2publishCore\Component\Core\Record\Model\Node;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

class RecordIterator
{
    /**
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    public function recurse(Node $node, Closure $closure): void
    {
        $records = [];
        if ($node instanceof Record) {
            $records[] = $node;
        } else {
            foreach ($node->getChildren() as $children) {
                foreach ($children as $child) {
                    $records[] = $child;
                }
            }
        }

        $visited = [];
        $stack = [];
        try {
            $this->recurseRecords($records, $closure, $visited, $stack);
        } catch (StopIteration $e) {
        }
    }

    /**
     * @param array<Record> $records
     * @param array<string, array<int, true>> $visited
     * @throws StopIteration
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    protected function recurseRecords(array $records, Closure $closure, array &$visited, array &$stack): void
    {
        foreach ($records as $record) {
            try {
                $stack[] = $record;
                $this->callClosure($record, $closure, $visited, $stack);
                foreach ($record->getChildren() as $children) {
                    $this->recurseRecords($children, $closure, $visited, $stack);
                }
            } catch (SkipChildren $e) {
            } finally {
                array_pop($stack);
            }
        }
    }

    /**
     * @param array<string, array<string|int, true>> $visited
     * @throws StopIteration
     * @throws SkipChildren
     * @noinspection PhpDocRedundantThrowsInspection
     */
    protected function callClosure(Record $record, Closure $closure, array &$visited, array &$stack): void
    {
        $classification = $record->getClassification();
        $id = $record->getId();

        if (isset($visited[$classification][$id])) {
            throw new SkipChildren();
        }
        $visited[$classification][$id] = true;

        $closure($record, $stack);
    }
}
