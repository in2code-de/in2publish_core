<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\Demand;

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandsCollection;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\Demand\DemandsCollection
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
        $demands->addSelect('foo', 'bar', 'baz', 14, $record1);
        $demands->addSelect('foo', 'bar', 'baz', 14, $record1);
        $demands->addSelect('foo', 'bar', 'baz', 14, $record2);
        $demands->addSelect('foo', 'bar', 'baz', 14, $record2);

        $expected = [];
        $expected['foo']['bar']['baz'][14]['table_foo\\4'] = $record1;
        $expected['foo']['bar']['baz'][14]['table_bar\\5'] = $record2;
        $this->assertSame($expected, $demands->getSelect());
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
        $demands->addJoin('foo_bar_mm', 'foo', 'bar', 'baz', 14, $record1);
        $demands->addJoin('foo_bar_mm', 'foo', 'bar', 'baz', 14, $record1);
        $demands->addJoin('foo_bar_mm', 'foo', 'bar', 'baz', 14, $record2);
        $demands->addJoin('foo_bar_mm', 'foo', 'bar', 'baz', 14, $record2);

        $expected = [];
        $expected['foo_bar_mm']['foo']['bar']['baz'][14]['table_foo\\4'] = $record1;
        $expected['foo_bar_mm']['foo']['bar']['baz'][14]['table_bar\\5'] = $record2;
        $this->assertSame($expected, $demands->getJoin());
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
        $demands->addFile(1, '/file/a', $record1);
        $demands->addFile(1, '/file/a', $record1);
        $demands->addFile(1, '/file/b', $record2);
        $demands->addFile(1, '/file/b', $record2);

        $expected = [];
        $expected[1]['/file/a']['table_foo\\4'] = $record1;
        $expected[1]['/file/b']['table_bar\\5'] = $record2;
        $this->assertSame($expected, $demands->getFiles());
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
        $demands->addSelect('foo', 'bar', 'baz', 14, $record1);

        $demands->unsetSelect('foo', 'baz', 14);

        $this->assertSame([], $demands->getSelect());
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
        $demands->addJoin('foo_bar_mm', 'foo', 'bar', 'baz', 14, $record1);

        $demands->unsetJoin('foo_bar_mm', 'foo', 'baz', 14);

        $this->assertSame([], $demands->getJoin());
    }

    /**
     * @covers ::uniqueRecordKey
     */
    public function testUniqueRecordKeyReturnsUniqueIdentifier(): void
    {
        $record1 = $this->createMock(DatabaseRecord::class);
        $record1->method('getClassification')->willReturn('table_');
        $record1->method('getId')->willReturn(41);

        $record2 = $this->createMock(DatabaseRecord::class);
        $record2->method('getClassification')->willReturn('table_4');
        $record2->method('getId')->willReturn(1);

        $demands = new DemandsCollection();
        $record1Key = $demands->uniqueRecordKey($record1);
        $record2Key = $demands->uniqueRecordKey($record2);

        $this->assertNotSame($record1Key, $record2Key);
    }
}
