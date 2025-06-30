<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Iterator;

use In2code\In2publishCore\Component\Core\Record\Iterator\IterationControls\StopIteration;
use In2code\In2publishCore\Component\Core\Record\Iterator\RecordIterator;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(RecordIterator::class, 'recurse')]
#[CoversMethod(RecordIterator::class, 'recurseRecords')]
#[CoversMethod(RecordIterator::class, 'callClosure')]
class RecordIteratorTest extends UnitTestCase
{
    public function testStopIterationWillImmediatelyStopTheProcess(): void
    {
        $child1 = new DatabaseRecord('bar', 1, [], [], []);

        $node = new DatabaseRecord('foo', 1, [], [], []);
        $node->addChild($child1);

        $count = 0;
        $self = $this;
        $closure = static function (Record $record) use ($self, &$count): void {
            ++$count;
            $self->assertSame('foo', $record->getClassification());
            $self->assertSame(1, $record->getId());
            throw new StopIteration();
        };

        $recordIterator = new RecordIterator();
        $recordIterator->recurse($node, $closure);

        $this->assertSame(1, $count);
    }

    public function testRecurseWillVisitEachRecordOnlyOnce(): void
    {
        $child1 = new DatabaseRecord('bar', 1, [], [], []);

        $node = new DatabaseRecord('foo', 1, [], [], []);
        $node->addChild($child1);
        $node->addChild($node);

        $child1->addChild($node);

        $expected = [
            $node,
            $child1,
        ];

        $history = [];
        $closure = static function (Record $record) use (&$history): void {
            $history[] = $record;
        };

        $recordIterator = new RecordIterator();
        $recordIterator->recurse($node, $closure);

        $this->assertSame($expected, $history);
    }
}
