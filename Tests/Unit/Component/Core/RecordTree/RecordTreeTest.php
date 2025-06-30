<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\RecordTree;

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(RecordTree::class, '__construct')]
#[CoversMethod(RecordTree::class, 'addChild')]
#[CoversMethod(RecordTree::class, 'getChildren')]
#[CoversMethod(RecordTree::class, 'getChild')]
#[CoversMethod(RecordTree::class, 'getClassification')]
#[CoversMethod(RecordTree::class, 'getId')]
class RecordTreeTest extends UnitTestCase
{
    public function testRecordTree(): void
    {
        $record1 = $this->createMock(DatabaseRecord::class);
        $record1->method('getClassification')->willReturn('table_foo');
        $record1->method('getId')->willReturn(1);

        $record2 = $this->createMock(DatabaseRecord::class);
        $record2->method('getClassification')->willReturn('table_bar');
        $record2->method('getId')->willReturn(2);

        $recordTree = new RecordTree([$record1, $record2]);

        $this->assertInstanceOf(RecordTree::class, $recordTree);
        $this->assertSame('_root', $recordTree->getClassification());
        $this->assertSame(-1, $recordTree->getId());
        $this->assertSame($record1, $recordTree->getChild('table_foo', 1));
        $this->assertSame($record2, $recordTree->getChild('table_bar', 2));
        $this->assertNull($recordTree->getChild('table_bar', 3));

        $expectedChildrenInTree = [
            'table_foo' => [
                1 => $record1,
            ],
            'table_bar' => [
                2 => $record2,
            ],
        ];
        $this->assertSame($expectedChildrenInTree, $recordTree->getChildren());
    }
}
