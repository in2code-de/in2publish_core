<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Record\Model\FileRecord
 */
class FileRecordTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::getClassification
     * @covers ::getId
     * @covers ::getState
     */
    public function testCreationOfNewFileRecord(): void
    {
        $fileRecord = new FileRecord(['identifier' => 'file1', 'storage' => 42],[]);
        $this->assertSame('42:file1', $fileRecord->getId());
        $this->assertSame('_file', $fileRecord->getClassification());
        $this->assertSame(Record::S_ADDED, $fileRecord->getState());
    }

    /**
     * @covers ::getForeignIdentificationProps
     */
    public function testGetForeignIdentificationProps(): void
    {
        $fileRecord = new FileRecord(['identifier' => 'file1', 'storage' => 42],[]);
        $this->assertSame([], $fileRecord->getForeignIdentificationProps());
    }

    /**
     * @covers ::__construct
     * @covers ::calculateState
     * @covers ::getState
     */
    public function testCalculateState(): void
    {
        $addedFileRecord =  new FileRecord(['identifier' => 'file1', 'storage' => 42],[]);
        $this->assertSame(Record::S_ADDED, $addedFileRecord->getState());

        $deletedFileRecord = new FileRecord([], ['identifier' => 'file1', 'storage' => 42]);
        $this->assertSame(Record::S_DELETED, $deletedFileRecord->getState());

        $unchangedFileRecord = new FileRecord(
            ['identifier' => 'file1', 'storage' => 42],
            ['identifier' => 'file1', 'storage' => 42]
        );
        $this->assertSame(Record::S_UNCHANGED, $unchangedFileRecord->getState());

        $changedFileRecord = new FileRecord(
            ['identifier' => 'file1', 'storage' => 42],
            ['identifier' => 'file2', 'storage' => 42]
        );
        $this->assertSame(Record::S_MOVED, $changedFileRecord->getState());

    }


}
