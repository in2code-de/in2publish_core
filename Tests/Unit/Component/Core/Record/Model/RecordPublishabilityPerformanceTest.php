<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use Generator;
use In2code\In2publishCore\Component\Core\Record\Model\AbstractDatabaseRecord;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * Reproduces the shape of the "publish a folder/page with thousands of records" workload: while
 * publishing, the publishability of the records is checked, and (through the workflow parent lookup)
 * the *shared parent* is repeatedly asked whether it is publishable.
 * Record::isPublishable() relies on hasUnfulfilledDependenciesRecursively(), which walks the parent's
 * entire dependency subtree.
 *
 * The tests count how often the dependency subtree is actually walked.
 * They can be run on every release to detect a reintroduction of the quadratic behaviour.
 *
 * @covers \In2code\In2publishCore\Component\Core\Record\Model\AbstractRecord::hasUnfulfilledDependenciesRecursively
 */
class RecordPublishabilityPerformanceTest extends UnitTestCase
{
    public function testDependencySubtreeIsWalkedOnlyOnceAcrossManyPublishabilityChecks(): void
    {
        $childCount = 250;
        $parent = $this->createParentWithChildren($childCount);

        // Mirror publishing: every record's publishability resolves to (and checks) the shared parent.
        $checks = $childCount + 1;
        for ($i = 0; $i < $checks; $i++) {
            $parent->hasUnfulfilledDependenciesRecursively();
        }

        self::assertSame(
            1,
            $parent->dependencyWalks,
            'The parent dependency subtree must be walked exactly once (memoized), not once per '
            . 'publishability check. A higher number means there is a quadratic performance issue.',
        );
    }

    public function testNumberOfDependencyWalksIsIndependentOfTreeSize(): void
    {
        $walksForTreeSize = function (int $childCount): int {
            $parent = $this->createParentWithChildren($childCount);
            $checks = $childCount + 1;
            for ($i = 0; $i < $checks; $i++) {
                $parent->hasUnfulfilledDependenciesRecursively();
            }
            return $parent->dependencyWalks;
        };

        $walksSmallTree = $walksForTreeSize(100);
        $walksLargeTree = $walksForTreeSize(400);

        // With memoization the work to repeatedly check publishability is constant (one walk) and does
        // not grow with the number of records. Without it, the walk count scales with the tree size.
        self::assertSame(
            $walksSmallTree,
            $walksLargeTree,
            'Repeated publishability checks must not scale with the number of records.',
        );
        self::assertSame(1, $walksLargeTree);
    }

    private function createParentWithChildren(int $childCount): AbstractDatabaseRecord
    {
        $parent = $this->createCountingRecord('foo', 1);
        for ($i = 1; $i <= $childCount; $i++) {
            $parent->addChild($this->createCountingRecord('foo', 1000 + $i));
        }
        return $parent;
    }

    /**
     * Creates an "added" database record (local only, no foreign) that counts how often its dependency
     * subtree is walked from the top (i.e. how often the memoization misses). Children are non-"pages"
     * records so they are part of the recursive dependency walk, just like content records below a page.
     */
    private function createCountingRecord(string $table, int $id): AbstractDatabaseRecord
    {
        return new class ($table, $id) extends AbstractDatabaseRecord {
            public int $dependencyWalks = 0;
            protected int $id;

            public function __construct(string $table, int $id)
            {
                $this->table = $table;
                $this->id = $id;
                $this->localProps = ['uid' => $id];
                $this->foreignProps = [];
                $this->ignoredProps = [];
                $this->state = $this->calculateState();
                $this->dependencies = $this->calculateDependencies();
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function getAllDependencies(array &$visited = []): Generator
            {
                if ([] === $visited) {
                    ++$this->dependencyWalks;
                }
                yield from parent::getAllDependencies($visited);
            }
        };
    }
}
