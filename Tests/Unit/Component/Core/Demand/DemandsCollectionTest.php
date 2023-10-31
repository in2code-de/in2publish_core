<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Demand;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Remover\JoinDemandRemover;
use In2code\In2publishCore\Component\Core\Demand\Remover\SelectDemandRemover;
use In2code\In2publishCore\Component\Core\Demand\Type\FileDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\JoinDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Demand\DemandsCollection
 */
class DemandsCollectionTest extends TestCase
{
    /**
     * @covers ::addSelect
     * @covers ::getSelect
     */
    public function testAddSelectAddsUniqueSelect(): void
    {
        $record1 = $this->createMock(DatabaseRecord::class);
        $record1->method('getClassification')->willReturn('table_foo');
        $record1->method('getId')->willReturn(4);

        $record2 = $this->createMock(DatabaseRecord::class);
        $record2->method('getClassification')->willReturn('table_bar');
        $record2->method('getId')->willReturn(5);

        $demands = new DemandsCollection();
        $demands->addDemand(new SelectDemand('foo', 'bar', 'baz', 14, $record1));
        $demands->addDemand(new SelectDemand('foo', 'bar', 'baz', 14, $record1));
        $demands->addDemand(new SelectDemand('foo', 'bar', 'baz', 14, $record2));
        $demands->addDemand(new SelectDemand('foo', 'bar', 'baz', 14, $record2));

        $expected = [];
        $expected['foo']['bar']['baz'][14]['table_foo\\4'] = $record1;
        $expected['foo']['bar']['baz'][14]['table_bar\\5'] = $record2;
        $this->assertSame($expected, $demands->getDemandsByType(SelectDemand::class));
    }

    /**
     * @covers ::addJoin
     * @covers ::getJoin
     */
    public function testAddSelectAddsUniqueJoin(): void
    {
        $record1 = $this->createMock(DatabaseRecord::class);
        $record1->method('getClassification')->willReturn('table_foo');
        $record1->method('getId')->willReturn(4);

        $record2 = $this->createMock(DatabaseRecord::class);
        $record2->method('getClassification')->willReturn('table_bar');
        $record2->method('getId')->willReturn(5);

        $demands = new DemandsCollection();
        $demands->addDemand(new JoinDemand('foo_bar_mm', 'foo', 'bar', 'baz', 14, $record1));
        $demands->addDemand(new JoinDemand('foo_bar_mm', 'foo', 'bar', 'baz', 14, $record1));
        $demands->addDemand(new JoinDemand('foo_bar_mm', 'foo', 'bar', 'baz', 14, $record2));
        $demands->addDemand(new JoinDemand('foo_bar_mm', 'foo', 'bar', 'baz', 14, $record2));

        $expected = [];
        $expected['foo_bar_mm']['foo']['bar']['baz'][14]['table_foo\\4'] = $record1;
        $expected['foo_bar_mm']['foo']['bar']['baz'][14]['table_bar\\5'] = $record2;
        $this->assertSame($expected, $demands->getDemandsByType(JoinDemand::class));
    }

    /**
     * @covers ::addFile
     * @covers ::getFiles
     */
    public function testAddSelectAddsUniqueFiles(): void
    {
        $record1 = $this->createMock(DatabaseRecord::class);
        $record1->method('getClassification')->willReturn('table_foo');
        $record1->method('getId')->willReturn(4);

        $record2 = $this->createMock(DatabaseRecord::class);
        $record2->method('getClassification')->willReturn('table_bar');
        $record2->method('getId')->willReturn(5);

        $demands = new DemandsCollection();
        $demands->addDemand(new FileDemand(1, '/file/a', $record1));
        $demands->addDemand(new FileDemand(1, '/file/a', $record1));
        $demands->addDemand(new FileDemand(1, '/file/b', $record2));
        $demands->addDemand(new FileDemand(1, '/file/b', $record2));

        $expected = [];
        $expected[1]['/file/a']['table_foo\\4'] = $record1;
        $expected[1]['/file/b']['table_bar\\5'] = $record2;
        $this->assertSame($expected, $demands->getDemandsByType(FileDemand::class));
    }

    /**
     * @covers ::addSelect
     * @covers ::unsetSelect
     * @covers ::getSelect
     */
    public function testUnsetSelectRemovesEntryAndEmptyParents(): void
    {
        $record1 = $this->createMock(DatabaseRecord::class);
        $record1->method('getClassification')->willReturn('table_foo');
        $record1->method('getId')->willReturn(4);

        $demands = new DemandsCollection();
        $demands->addDemand(new SelectDemand('foo', 'bar', 'baz', 14, $record1));

        $demands->unsetDemand(new SelectDemandRemover('foo', 'baz', 14));

        $this->assertSame([], $demands->getDemandsByType(SelectDemand::class));
    }

    /**
     * @covers ::addJoin
     * @covers ::unsetJoin
     * @covers ::getJoin
     */
    public function testUnsetJoinRemovesEntryAndEmptyParents(): void
    {
        $record1 = $this->createMock(DatabaseRecord::class);
        $record1->method('getClassification')->willReturn('table_foo');
        $record1->method('getId')->willReturn(4);

        $demands = new DemandsCollection();
        $demands->addDemand(new JoinDemand('foo_bar_mm', 'foo', 'bar', 'baz', 14, $record1));

        $demands->unsetDemand(new JoinDemandRemover('foo_bar_mm', 'foo', 'baz', 14));

        $this->assertSame([], $demands->getDemandsByType(JoinDemand::class));
    }
}
