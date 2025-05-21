<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(FileRecord::class, '__construct')]
#[CoversMethod(FileRecord::class, 'getClassification')]
#[CoversMethod(FileRecord::class, 'getId')]
#[CoversMethod(FileRecord::class, 'getState')]
#[CoversMethod(FileRecord::class, 'getForeignIdentificationProps')]
#[CoversMethod(FileRecord::class, 'calculateState')]
class FileRecordTest extends UnitTestCase
{
    public function testCreationOfNewFileRecord(): void
    {
        $fileRecord = new FileRecord(['identifier' => 'file1', 'storage' => 42], []);
        $this->assertSame('42:file1', $fileRecord->getId());
        $this->assertSame('_file', $fileRecord->getClassification());
        $this->assertSame(Record::S_ADDED, $fileRecord->getState());
    }

    public function testGetForeignIdentificationProps(): void
    {
        $fileRecord = new FileRecord(['identifier' => 'file1', 'storage' => 42], []);
        $this->assertSame([], $fileRecord->getForeignIdentificationProps());
    }

    public function testCalculateState(): void
    {
        $addedFileRecord = new FileRecord(['identifier' => 'file1', 'storage' => 42], []);
        $this->assertSame(Record::S_ADDED, $addedFileRecord->getState());

        $deletedFileRecord = new FileRecord([], ['identifier' => 'file1', 'storage' => 42]);
        $this->assertSame(Record::S_DELETED, $deletedFileRecord->getState());

        $unchangedFileRecord = new FileRecord(
            ['identifier' => 'file1', 'storage' => 42],
            ['identifier' => 'file1', 'storage' => 42],
        );
        $this->assertSame(Record::S_UNCHANGED, $unchangedFileRecord->getState());

        $changedFileRecord = new FileRecord(
            ['identifier' => 'file1', 'storage' => 42],
            ['identifier' => 'file2', 'storage' => 42],
        );
        $this->assertSame(Record::S_CHANGED, $changedFileRecord->getState());

        $movedFileRecord = new FileRecord(
            ['identifier' => 'file1', 'folder_hash' => '/bar', 'storage' => 42],
            ['identifier' => 'file1', 'folder_hash' => '/foo', 'storage' => 42],
        );
        $this->assertSame(Record::S_MOVED, $movedFileRecord->getState());

        $replacedFileRecord = new FileRecord(
            ['identifier' => 'file1', 'folder_hash' => '/bar', 'storage' => 42, 'sha1' => '234572364958734'],
            ['identifier' => 'file1', 'folder_hash' => '/foo', 'storage' => 42, 'sha1' => '238497569384765'],
        );
        $this->assertSame(Record::S_CHANGED, $replacedFileRecord->getState());
    }
}
