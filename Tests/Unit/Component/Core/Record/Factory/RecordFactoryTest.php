<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Record\Factory;

use In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactory;
use In2code\In2publishCore\Component\Core\Record\Factory\DatabaseRecordFactoryFactory;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\MmDatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\PageTreeRootRecord;
use In2code\In2publishCore\Component\Core\RecordIndex;
use In2code\In2publishCore\Event\DecideIfRecordShouldBeIgnored;
use In2code\In2publishCore\Service\Configuration\IgnoredFieldsService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;

#[CoversMethod(RecordFactory::class, 'createPageTreeRootRecord')]
#[CoversMethod(RecordFactory::class, 'finishRecord')]
#[CoversMethod(RecordFactory::class, 'createDatabaseRecord')]
#[CoversMethod(RecordFactory::class, 'shouldIgnoreRecord')]
#[CoversMethod(RecordFactory::class, 'createMmRecord')]
#[CoversMethod(RecordFactory::class, 'createFileRecord')]
#[CoversMethod(RecordFactory::class, 'createFolderRecord')]
class RecordFactoryTest extends UnitTestCase
{
    public function testCreateRootTreeRecord(): void
    {
        $recordFactory = new RecordFactory($this->createMock(DatabaseRecordFactoryFactory::class));

        $recordIndex = $this->createMock(RecordIndex::class);
        $recordIndex->expects($this->once())->method('addRecord');

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->once())->method('dispatch');

        $recordFactory->injectRecordIndex($recordIndex);
        $recordFactory->injectEventDispatcher($eventDispatcher);

        $record = $recordFactory->createPageTreeRootRecord();

        $this->assertInstanceOf(PageTreeRootRecord::class, $record);
    }

    public function testDatabaseRecordIsCreatedAndAddedToRecordIndex(): void
    {
        $databaseRecordFactoryFactory = $this->createMock(DatabaseRecordFactoryFactory::class);
        $recordFactory = new RecordFactory($databaseRecordFactoryFactory);

        $table = 'table_foo';

        $ignoreFieldsService = $this->createMock(IgnoredFieldsService::class);
        $ignoreFieldsService->expects($this->once())->method('getIgnoredFields')->with($table)->willReturn([]);

        $databaseRecordFactoryFactory->expects($this->once())
                                     ->method('createFactoryForTable')
                                     ->with($table)
                                     ->willReturn($this->createMock(DatabaseRecordFactory::class));

        $recordIndex = $this->createMock(RecordIndex::class);
        $recordIndex->expects($this->once())->method('addRecord');

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->exactly(2))->method('dispatch');

        $recordFactory->injectIgnoredFieldsService($ignoreFieldsService);
        $recordFactory->injectEventDispatcher($eventDispatcher);
        $recordFactory->injectRecordIndex($recordIndex);

        $record = $recordFactory->createDatabaseRecord($table, 1, [
            'field_foo' => 'foo',
            'ignored_field' => 'bar',
        ], []);

        $this->assertInstanceOf(DatabaseRecord::class, $record);
    }

    public function testDatabaseRecordIsNotCreatedAndAddedToRecordIndexIfItShouldBeIgnored(): void
    {
        $databaseRecordFactoryFactory = $this->createMock(DatabaseRecordFactoryFactory::class);
        $recordFactory = new RecordFactory($databaseRecordFactoryFactory);

        $table = 'table_foo';

        $ignoreFieldsService = $this->createMock(IgnoredFieldsService::class);
        $ignoreFieldsService->method('getIgnoredFields')->willReturn([]);

        $databaseRecordFactoryFactory = $this->createMock(DatabaseRecordFactoryFactory::class);
        $databaseRecordFactoryFactory->method('createFactoryForTable')->willReturn(
            $this->createMock(DatabaseRecordFactory::class),
        );

        $listenerProvider = $this->createMock(ListenerProvider::class);
        $listenerProvider->method('getListenersForEvent')->willReturnCallback(function (object $event) {
            if ($event instanceof DecideIfRecordShouldBeIgnored) {
                return [
                    function (DecideIfRecordShouldBeIgnored $event) {
                        $event->shouldIgnore();
                    },
                ];
            }
            return [];
        });

        $eventDispatcher = new EventDispatcher($listenerProvider);

        $recordFactory->injectIgnoredFieldsService($ignoreFieldsService);
        $recordFactory->injectEventDispatcher($eventDispatcher);

        $record = $recordFactory->createDatabaseRecord($table, 1, ['ignored_field' => 'bar'], []);

        $this->assertNull($record);
    }

    public function testMmRecordIsCreatedAndAddedToRecordIndex(): void
    {
        $recordFactory = new RecordFactory($this->createMock(DatabaseRecordFactoryFactory::class));

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

    public function testFileRecordIsCreatedAndAddedToRecordIndex(): void
    {
        $recordFactory = new RecordFactory($this->createMock(DatabaseRecordFactoryFactory::class));

        $recordIndex = $this->createMock(RecordIndex::class);
        $recordIndex->expects($this->once())->method('addRecord');

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->exactly(2))->method('dispatch');

        $ignoredFieldsService = $this->createMock(IgnoredFieldsService::class);
        $ignoredFieldsService->method('getIgnoredFields')->willReturn([]);

        $recordFactory->injectIgnoredFieldsService($ignoredFieldsService);
        $recordFactory->injectEventDispatcher($eventDispatcher);
        $recordFactory->injectRecordIndex($recordIndex);

        $record = $recordFactory->createFileRecord(['field_foo' => 'value_foo'], []);

        $this->assertInstanceOf(FileRecord::class, $record);
    }

    public function testFolderRecordIsCreatedAndAddedToRecordIndex(): void
    {
        $recordFactory = new RecordFactory($this->createMock(DatabaseRecordFactoryFactory::class));

        $recordIndex = $this->createMock(RecordIndex::class);
        $recordIndex->expects($this->once())->method('addRecord');

        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $eventDispatcher->expects($this->exactly(2))->method('dispatch');

        $recordFactory->injectEventDispatcher($eventDispatcher);
        $recordFactory->injectRecordIndex($recordIndex);

        $record = $recordFactory->createFolderRecord(
            ['field_foo' => 'value_foo'],
            [],
        );

        $this->assertInstanceOf(FolderRecord::class, $record);
    }
}
