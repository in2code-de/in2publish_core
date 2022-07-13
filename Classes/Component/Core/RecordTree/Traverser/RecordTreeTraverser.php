<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\RecordTree\Traverser;

use Closure;
use In2code\In2publishCore\Component\Core\Record\Model\Node;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;

use function ksort;

use const SORT_NUMERIC;

class RecordTreeTraverser
{
    public const OP_SKIP = 'skip';
    public const OP_IGNORE = 'ignore';
    public const EVENT_ENTER = 'enter';
    public const EVENT_LEAVE = 'leave';
    private array $visitors = [];

    public function addVisitor(Closure $closure): void
    {
        $this->visitors[] = $closure;
    }

    public function run(RecordTree $recordTree): void
    {
        $this->traverse($recordTree, $this->visitors);
    }

    private function traverse(Node $node, array $visitors, array &$visited = []): void
    {
        foreach ($node->getChildren() as $children) {
            foreach ($children as $child) {
                $classification = $child->getClassification();
                $id = $child->getId();
                if (isset($visited[$classification][$id])) {
                    continue;
                }
                $visited[$classification][$id] = true;

                $ignored = [];
                $skipped = [];
                foreach ($visitors as $index => $visitor) {
                    $op = $visitor(self::EVENT_ENTER, $child);
                    if (self::OP_SKIP === $op) {
                        $skipped[$index] = $visitor;
                        unset($visitors[$index]);
                    }
                    if (self::OP_IGNORE === $op) {
                        $ignored[$index] = $visitor;
                        unset($visitors[$index]);
                    }
                }

                if (!empty($visitors)) {
                    $this->traverse($child, $visitors, $visited);
                }

                if (!empty($skipped)) {
                    foreach ($skipped as $index => $visitor) {
                        $visitors[$index] = $visitor;
                    }
                    ksort($visitors, SORT_NUMERIC);
                }
                foreach ($visitors as $visitor) {
                    $visitor(self::EVENT_LEAVE, $child);
                }
                if (!empty($ignored)) {
                    foreach ($ignored as $index => $visitor) {
                        $visitors[$index] = $visitor;
                    }
                    ksort($visitors, SORT_NUMERIC);
                }
            }
        }
    }
}
