<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\Publisher\DatabaseRecordPublisher;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Publisher\DatabaseRecordPublisher
 */
class DatabaseRecordPublisherTest extends UnitTestCase
{
    /**
     * @covers ::canPublish
     */
    public function testCanPublishReturnsTrueForDatabaseRecordsOnly()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $this->assertTrue($databaseRecordPublisher->canPublish($databaseRecord));

        $folderRecord = $this->createMock(FolderRecord::class);
        $this->assertFalse($databaseRecordPublisher->canPublish($folderRecord));

        $fileRecord = $this->createMock(FileRecord::class);
        $this->assertFalse($databaseRecordPublisher->canPublish($fileRecord));
    }

    /**
     * @covers ::publish
     */
    public function testPublishInsertsAddedRecords()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();
        $foreignDatabase = $this->getForeignDatabaseMock($databaseRecordPublisher);
        $foreignDatabase->expects($this->once())->method('insert')->with(
            'tx_in2publishcore_domain_model_test',
            ['foo' => 'bar'],
        );

        $addedRecord = $this->createMock(DatabaseRecord::class);
        $addedRecord->method('getLocalProps')->willReturn(['foo' => 'bar']);
        $addedRecord->method('getClassification')->willReturn('tx_in2publishcore_domain_model_test');
        $addedRecord->method('getState')->willReturn(Record::S_ADDED);

        $databaseRecordPublisher->publish($addedRecord);
    }

    /**
     * @covers ::publish
     */
    public function testPublishDeletesRemovedRecords()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();
        $foreignDatabase = $this->getForeignDatabaseMock($databaseRecordPublisher);
        $foreignDatabase->expects($this->once())->method('delete')->with(
            'tx_in2publishcore_domain_model_test',
            ['foo' => 'bar'],
        );

        $addedRecord = $this->createMock(DatabaseRecord::class);
        $addedRecord->method('getForeignIdentificationProps')->willReturn(['foo' => 'bar']);
        $addedRecord->method('getClassification')->willReturn('tx_in2publishcore_domain_model_test');
        $addedRecord->method('getState')->willReturn(Record::S_DELETED);

        $databaseRecordPublisher->publish($addedRecord);
    }

    /**
     * @covers ::publish
     */
    public function testPublishUpdatesChangedRecords()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();
        $foreignDatabase = $this->getForeignDatabaseMock($databaseRecordPublisher);
        $foreignDatabase->expects($this->once())->method('update')->with(
            'tx_in2publishcore_domain_model_test',
            ['prop1' => 'localValue', 'prop2' => 'localValue'],
        );

        $addedRecord = $this->createMock(DatabaseRecord::class);
        $addedRecord->method('getLocalProps')->willReturn(['prop1' => 'localValue', 'prop2' => 'localValue']);
        $addedRecord->method('getForeignProps')->willReturn(['prop1' => 'foreignValue']);
        $addedRecord->method('getClassification')->willReturn('tx_in2publishcore_domain_model_test');
        $addedRecord->method('getState')->willReturn('');

        $databaseRecordPublisher->publish($addedRecord);
    }

    /**
     * @covers ::finish
     */
    public function testFinishCommitsDatabaseChanges()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();
        $foreignDatabase = $this->getForeignDatabaseMock($databaseRecordPublisher);;
        $foreignDatabase->method('isTransactionActive')->willReturn(true);

        $foreignDatabase->expects($this->once())->method('commit');

        $databaseRecordPublisher->finish();
    }

    /**
     * @covers ::cancel
     */
    public function testCancelRollsBackDatabaseChanges()
    {
        $databaseRecordPublisher = new DatabaseRecordPublisher();
        $foreignDatabase = $this->getForeignDatabaseMock($databaseRecordPublisher);
        $foreignDatabase->method('isTransactionActive')->willReturn(true);
        $foreignDatabase->expects($this->once())->method('rollBack');

        $databaseRecordPublisher->cancel();
    }

    /**
     * @param DatabaseRecordPublisher $databaseRecordPublisher
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function getForeignDatabaseMock(DatabaseRecordPublisher $databaseRecordPublisher): MockObject
    {
        $foreignDatabase = $this->createMock(Connection::class);
        $reflection = new \ReflectionProperty(DatabaseRecordPublisher::class, 'foreignDatabase');
        $reflection->setAccessible(true);
        $reflection->setValue($databaseRecordPublisher, $foreignDatabase);
        return $foreignDatabase;
    }
}
