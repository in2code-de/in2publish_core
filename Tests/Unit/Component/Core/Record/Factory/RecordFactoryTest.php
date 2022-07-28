<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Factory;

/*
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory
 */

use In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactory;
use In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactoryFactory;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\MmDatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\PageTreeRootRecord;
use In2code\In2publishCore\Component\Core\RecordIndex;
use In2code\In2publishCore\Service\Configuration\IgnoredFieldsService;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory
 */
class RecordFactoryTest extends UnitTestCase
{
    /**
     * @covers ::createPageTreeRootRecord
     * @covers ::finishRecord
     */
    public function testCreateRootTreeRecord(): void
    {
        $recordFactory = new RecordFactory();

        $recordIndex = $this->createMock(RecordIndex::class);
        $recordIndex->expects($this->once())->method('addRecord');

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->once())->method('dispatch');

        $recordFactory->injectRecordIndex($recordIndex);
        $recordFactory->injectEventDispatcher($eventDispatcher);

        $record = $recordFactory->createPageTreeRootRecord();

        $this->assertInstanceOf(PageTreeRootRecord::class, $record);
    }

    /**
     * @covers ::createDatabaseRecord
     * @covers ::finishRecord
     */
    public function testDatabaseRecordIsCreatedAndAddedToRecordIndex(): void
    {
        $recordFactory = new RecordFactory();

        $table = 'table_foo';

        $ignoreFieldsService = $this->createMock(IgnoredFieldsService::class);
        $ignoreFieldsService->expects($this->once())->method('getIgnoredFields')->with($table)->willReturn([]);

        $databaseRecordFactoryFactory = $this->createMock(DatabaseRecordFactoryFactory::class);
        $databaseRecordFactoryFactory->expects($this->once())->method('createFactoryForTable')->with($table)
            ->willReturn($this->createMock(DatabaseRecordFactory::class));

        $recordIndex = $this->createMock(RecordIndex::class);
        $recordIndex->expects($this->once())->method('addRecord');

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->exactly(2))->method('dispatch');

        $recordFactory->injectIgnoredFieldsService($ignoreFieldsService);
        $recordFactory->injectDatabaseRecordFactoryFactory($databaseRecordFactoryFactory);
        $recordFactory->injectEventDispatcher($eventDispatcher);
        $recordFactory->injectRecordIndex($recordIndex);

        $record = $recordFactory->createDatabaseRecord($table, 1, [
            'field_foo' => 'foo',
            'ignored_field' => 'bar'
        ], []);

        $this->assertInstanceOf(DatabaseRecord::class, $record);
    }

    /**
     * @covers ::createDatabaseRecord
     * @covers ::finishRecord
     * @covers ::shouldIgnoreRecord
     * TODO: how to test ignored fields?
     */
    public function testDatabaseRecordIsNotCreatedAndAddedToRecordIndexIfItContainsOnlyIgnoredFields(): void
    {
        $recordFactory = new RecordFactory();

        $table = 'table_foo';

        $ignoreFieldsService = $this->createMock(IgnoredFieldsService::class);
        $ignoreFieldsService->expects($this->once())->method('getIgnoredFields')->with($table)
            ->willReturn(['ignored_field']);

        $databaseRecordFactoryFactory = $this->createMock(DatabaseRecordFactoryFactory::class);
        $databaseRecordFactoryFactory->expects($this->once())->method('createFactoryForTable')->with($table)
            ->willReturn($this->createMock(DatabaseRecordFactory::class));

        $recordIndex = $this->createMock(RecordIndex::class);
        $recordIndex->expects($this->once())->method('addRecord');

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->exactly(2))->method('dispatch');

        $recordFactory->injectIgnoredFieldsService($ignoreFieldsService);
        $recordFactory->injectDatabaseRecordFactoryFactory($databaseRecordFactoryFactory);
        $recordFactory->injectEventDispatcher($eventDispatcher);
        $recordFactory->injectRecordIndex($recordIndex);

        $record = $recordFactory->createDatabaseRecord($table, 1, ['ignored_field' => 'bar'], []);

        $this->assertInstanceOf(DatabaseRecord::class, $record);
    }

    /**
     * @covers ::createMmRecord
     */
    public function testMmRecordIsCreatedAndAddedToRecordIndex(): void
    {
        $recordFactory = new RecordFactory();

        $table = 'mm_table';

        $recordIndex = $this->createMock(RecordIndex::class);
        $recordIndex->expects($this->once())->method('addRecord');

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->exactly(2))->method('dispatch');

        $recordFactory->injectEventDispatcher($eventDispatcher);
        $recordFactory->injectRecordIndex($recordIndex);

        $record = $recordFactory->createMmRecord($table, 'some_property_hash', [], []);

        $this->assertInstanceOf(MmDatabaseRecord::class, $record);
    }

    /**
     * @covers ::createFileRecord
     * @covers ::finishRecord
     * @covers ::shouldIgnoreRecord
     */
    public function testFileRecordIsCreatedAndAddedToRecordIndex(): void
    {
        $recordFactory = new RecordFactory();

        $recordIndex = $this->createMock(RecordIndex::class);
        $recordIndex->expects($this->once())->method('addRecord');

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->exactly(2))->method('dispatch');

        $recordFactory->injectEventDispatcher($eventDispatcher);
        $recordFactory->injectRecordIndex($recordIndex);

        $record = $recordFactory->createFileRecord(['field_foo' => 'value_foo'],[]);

        $this->assertInstanceOf(FileRecord::class, $record);
    }

    /**
     * @covers ::createFolderRecord
     * @covers ::finishRecord
     * @covers ::shouldIgnoreRecord
     */
    public function testFolderRecordIsCreatedAndAddedToRecordIndex(): void
    {
        $recordFactory = new RecordFactory();

        $recordIndex = $this->createMock(RecordIndex::class);
        $recordIndex->expects($this->once())->method('addRecord');

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->exactly(2))->method('dispatch');

        $recordFactory->injectEventDispatcher($eventDispatcher);
        $recordFactory->injectRecordIndex($recordIndex);

        $record = $recordFactory->createFolderRecord(
            'combined_identifier',
            ['field_foo' => 'value_foo'],
            []
        );

        $this->assertInstanceOf(FolderRecord::class, $record);
    }
}
