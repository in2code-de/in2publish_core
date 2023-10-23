<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\FileHandling\Service\FalDriverService;
use In2code\In2publishCore\Component\Core\Publisher\FileRecordPublisher;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\AssetTransmitter;
use In2code\In2publishCore\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Publisher\FileRecordPublisher
 */
class FileRecordPublisherTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::canPublish
     */
    public function testCanPublishReturnsTrueForFileRecordsOnly()
    {
        $fileRecordPublisher = new FileRecordPublisher();

        $fileRecord = $this->createMock(FileRecord::class);
        $this->assertTrue($fileRecordPublisher->canPublish($fileRecord));

        $folderRecord = $this->createMock(FolderRecord::class);
        $this->assertFalse($fileRecordPublisher->canPublish($folderRecord));

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $this->assertFalse($fileRecordPublisher->canPublish($databaseRecord));
    }

    /**
     * @covers ::publish
     */
    public function testPublishDeletesRemovedFile()
    {
        $fileRecordPublisher = new FileRecordPublisher();
        $foreignDatabase = $this->createMock(Connection::class);

        $deletedFile = $this->createMock(FileRecord::class);
        $deletedFile->method('getClassification')->willReturn('_file');
        $deletedFile->method('getState')->willReturn(Record::S_DELETED);
        $deletedFile->method('getForeignProps')->willReturn(
            ['storage' => 1, 'identifier' => 'bar', 'identifier_hash' => 'baz'],
        );

        $reflectionProperty = new \ReflectionProperty($fileRecordPublisher, 'requestToken');
        $reflectionProperty->setAccessible(true);

        $foreignDatabase->expects($this->once())->method('insert')->with(
            'tx_in2publishcore_filepublisher_task',
            [
                'request_token' => $reflectionProperty->getValue($fileRecordPublisher),
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'storage_uid' => $deletedFile->getForeignProps()['storage'],
                'identifier' => $deletedFile->getForeignProps()['identifier'],
                'identifier_hash' => $deletedFile->getForeignProps()['identifier_hash'],
                'file_action' => $fileRecordPublisher::A_DELETE,
            ],
        );
        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);

        $fileRecordPublisher->publish($deletedFile);
    }

    /**
     * @covers ::publish
     */
    public function testPublishTransmitsAddedRecord()
    {
        $fileRecordPublisher = new FileRecordPublisher();
        $foreignDatabase = $this->createMock(Connection::class);
        $falDriverService = $this->createMock(FalDriverService::class);
        $mockDriver = $this->createMock(DriverInterface::class);

        $structure = [
            'Api.php' => '',
        ];
        $root = vfsStream::setup('root', null, $structure);
        $file = $root->url() . '/Api.php';
        $mockDriver->method('getFileForLocalProcessing')->willReturn($file);
        $falDriverService->method('getDriver')->willReturn($mockDriver);
        $assetTransmitter = $this->createMock(AssetTransmitter::class);

        $addedFile = $this->createMock(FileRecord::class);
        $addedFile->method('getClassification')->willReturn('_file');
        $addedFile->method('getState')->willReturn(Record::S_ADDED);
        $addedFile->method('getLocalProps')->willReturn(
            ['storage' => 1, 'identifier' => 'bar', 'identifier_hash' => 'baz'],
        );

        $reflectionProperty = new \ReflectionProperty($fileRecordPublisher, 'requestToken');
        $reflectionProperty->setAccessible(true);

        $foreignDatabase->expects($this->once())->method('insert')->with(
            'tx_in2publishcore_filepublisher_task',
            [
                'request_token' => $reflectionProperty->getValue($fileRecordPublisher),
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'storage_uid' => $addedFile->getLocalProps()['storage'],
                'identifier' => $addedFile->getLocalProps()['identifier'],
                'identifier_hash' => $addedFile->getLocalProps()['identifier_hash'],
                'file_action' => $fileRecordPublisher::A_INSERT,
                'temp_identifier_hash' => '',
            ],
        );

        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);
        $fileRecordPublisher->injectFalDriverService($falDriverService);
        $fileRecordPublisher->injectAssetTransmitter($assetTransmitter);

        $fileRecordPublisher->publish($addedFile);
    }

    /**
     * @covers ::publish
     */
    public function testPublishTransmitsChangedRecord()
    {
        $fileRecordPublisher = new FileRecordPublisher();
        $foreignDatabase = $this->createMock(Connection::class);
        $falDriverService = $this->createMock(FalDriverService::class);
        $mockDriver = $this->createMock(DriverInterface::class);

        $structure = [
            'Api.php' => '',
        ];
        $root = vfsStream::setup('root', null, $structure);
        $file = $root->url() . '/Api.php';
        $mockDriver->method('getFileForLocalProcessing')->willReturn($file);
        $falDriverService->method('getDriver')->willReturn($mockDriver);
        $assetTransmitter = $this->createMock(AssetTransmitter::class);

        $changedFile = $this->createMock(FileRecord::class);
        $changedFile->method('getClassification')->willReturn('_file');
        $changedFile->method('getState')->willReturn(Record::S_CHANGED);
        $changedFile->method('getLocalProps')->willReturn(
            ['storage' => 1, 'identifier' => 'bar', 'identifier_hash' => 'baz'],
        );
        $changedFile->method('getForeignProps')->willReturn(
            ['storage' => 2, 'identifier' => 'bar2', 'identifier_hash' => 'baz2'],
        );

        $reflectionProperty = new \ReflectionProperty($fileRecordPublisher, 'requestToken');
        $reflectionProperty->setAccessible(true);

        $foreignDatabase->expects($this->once())->method('insert')->with(
            'tx_in2publishcore_filepublisher_task',
            [
                'request_token' => $reflectionProperty->getValue($fileRecordPublisher),
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'storage_uid' => $changedFile->getLocalProps()['storage'],
                'identifier' => $changedFile->getLocalProps()['identifier'],
                'identifier_hash' => $changedFile->getLocalProps()['identifier_hash'],
                'file_action' => $fileRecordPublisher::A_UPDATE,
                'temp_identifier_hash' => '',
            ],
        );

        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);
        $fileRecordPublisher->injectFalDriverService($falDriverService);
        $fileRecordPublisher->injectAssetTransmitter($assetTransmitter);

        $fileRecordPublisher->publish($changedFile);
    }

    /**
     * @covers ::publish
     */
    public function testPublishTransmitsMovedRecord()
    {
        $fileRecordPublisher = new FileRecordPublisher();
        $foreignDatabase = $this->createMock(Connection::class);

        $movedFile = $this->createMock(FileRecord::class);
        $movedFile->method('getClassification')->willReturn('_file');
        $movedFile->method('getState')->willReturn(Record::S_MOVED);
        $movedFile->method('getLocalProps')->willReturn(
            ['storage' => 1, 'identifier' => 'bar', 'identifier_hash' => 'baz'],
        );
        $movedFile->method('getForeignProps')->willReturn(
            ['storage' => 1, 'identifier' => 'bar_foreign', 'identifier_hash' => 'baz_foreign'],
        );

        $reflectionProperty = new \ReflectionProperty($fileRecordPublisher, 'requestToken');
        $reflectionProperty->setAccessible(true);

        $foreignDatabase->expects($this->once())->method('insert')->with(
            'tx_in2publishcore_filepublisher_task',
            [
                'request_token' => $reflectionProperty->getValue($fileRecordPublisher),
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'storage_uid' => $movedFile->getLocalProps()['storage'],
                'identifier' => $movedFile->getLocalProps()['identifier'],
                'identifier_hash' => $movedFile->getLocalProps()['identifier_hash'],
                'previous_identifier' => $movedFile->getForeignProps()['identifier'],
                'file_action' => $fileRecordPublisher::A_RENAME,
            ],
        );
        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);

        $fileRecordPublisher->publish($movedFile);
    }
}
