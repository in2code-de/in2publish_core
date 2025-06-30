<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FolderInfo;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(FolderRecord::class, '__construct')]
#[CoversMethod(FolderRecord::class, 'getClassification')]
#[CoversMethod(FolderRecord::class, 'getId')]
#[CoversMethod(FolderRecord::class, 'getLocalProps')]
#[CoversMethod(FolderRecord::class, 'getForeignProps')]
#[CoversMethod(FolderRecord::class, 'getForeignIdentificationProps')]
#[CoversMethod(FolderRecord::class, 'getState')]
#[CoversMethod(FolderRecord::class, 'calculateState')]
class FolderRecordTest extends UnitTestCase
{
    public function testConstructor(): void
    {
        $folderRecord = new FolderRecord((new FolderInfo(42, 'folder_name', 'blala'))->toArray(), []);
        $this->assertInstanceOf(FolderRecord::class, $folderRecord);
        $this->assertSame('42:folder_name', $folderRecord->getId());
        $this->assertSame(
            ['storage' => 42, 'identifier' => 'folder_name', 'name' => 'blala'],
            $folderRecord->getLocalProps()
        );
        $this->assertSame([], $folderRecord->getForeignProps());
        $this->assertSame(FolderRecord::CLASSIFICATION, $folderRecord->getClassification());
        $this->expectExceptionCode(3424165576);
        $this->expectExceptionMessage('NOT IMPLEMENTED');
        $folderRecord->getForeignIdentificationProps();
    }

    public function testCalculateState(): void
    {
        $props = (new FolderInfo(42, 'folder_name', 'blala'))->toArray();
        $folderRecord = new FolderRecord($props, []);
        $this->assertSame(Record::S_ADDED, $folderRecord->getState());

        $folderRecord = new FolderRecord($props, $props);
        $this->assertSame(Record::S_UNCHANGED, $folderRecord->getState());

        $props2 = (new FolderInfo(42, 'other_folder_name', 'foooo'))->toArray();
        $folderRecord = new FolderRecord($props, $props2);
        $this->assertSame(Record::S_CHANGED, $folderRecord->getState());

        $folderRecord = new FolderRecord([], $props);
        $this->assertSame(Record::S_DELETED, $folderRecord->getState());
    }
}
