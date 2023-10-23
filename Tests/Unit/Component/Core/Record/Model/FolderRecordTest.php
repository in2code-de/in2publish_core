<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Model;

use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;
use LogicException;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Record\Model\FolderRecord
 */
class FolderRecordTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::getClassification
     * @covers ::getId
     * @covers ::getLocalProps
     * @covers ::getForeignProps
     * @covers ::getForeignIdentificationProps
     */
    public function testConstructor(): void
    {
        $folderRecord = new FolderRecord('42:folder_name', ['prop_1' => 'value_1'], []);
        $this->assertInstanceOf(FolderRecord::class, $folderRecord);
        $this->assertSame('42:folder_name', $folderRecord->getId());
        $this->assertSame(['prop_1' => 'value_1'], $folderRecord->getLocalProps());
        $this->assertSame([], $folderRecord->getForeignProps());
        $this->assertSame(FolderRecord::CLASSIFICATION, $folderRecord->getClassification());

        $this->expectExceptionObject(new LogicException('NOT IMPLEMENTED'));
        $folderRecord->getForeignIdentificationProps();
    }

    /**
     * @covers ::getState
     * @covers ::calculateState
     */
    public function testCalculateState(): void
    {
        $folderRecord = new FolderRecord('42:folder_name', ['prop_1' => 'value_1'], []);
        $this->assertSame(Record::S_ADDED, $folderRecord->getState());

        $folderRecord = new FolderRecord('42:folder_name', ['prop_1' => 'value_1'], ['prop_1' => 'value_1']);
        $this->assertSame(Record::S_UNCHANGED, $folderRecord->getState());

        $folderRecord = new FolderRecord('42:folder_name', ['prop_1' => 'value_1'], ['prop_1' => 'value_2']);
        $this->assertSame(Record::S_CHANGED, $folderRecord->getState());

        $folderRecord = new FolderRecord('42:folder_name', [],['prop_1' => 'value_2']);
        $this->assertSame(Record::S_DELETED, $folderRecord->getState());
    }
}
