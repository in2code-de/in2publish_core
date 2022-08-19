<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\Publisher\FolderRecordPublisher;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Database\Connection;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Publisher\FolderRecordPublisher
 */
class FolderRecordPublisherTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::canPublish
     */
    public function testCanPublishReturnsTrueForFileRecordsOnly()
    {
        $folderRecordPublisher = new FolderRecordPublisher();

        $folderRecord = $this->createMock(FolderRecord::class);
        $this->assertTrue($folderRecordPublisher->canPublish($folderRecord));

        $fileRecord = $this->createMock(FileRecord::class);
        $this->assertFalse($folderRecordPublisher->canPublish($fileRecord));

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $this->assertFalse($folderRecordPublisher->canPublish($databaseRecord));
    }

    /**
     * @covers ::publish
     */
    public function testPublishRemovesDeletedFolder()
    {
        $folderRecordPublisher = new FolderRecordPublisher();
        $foreignDatabase =  $this->createMock(Connection::class);
        $deletedFolder = $this->createMock(FileRecord::class);
        $deletedFolder->method('getClassification')->willReturn('_folder');
        $deletedFolder->method('getState')->willReturn(Record::S_DELETED);
        $deletedFolder->method('getForeignProps')->willReturn(
            ['storage' => 1, 'identifier' => 'bar', 'combinedIdentifier' => '1:bar']
        );

        $reflectionProperty = new \ReflectionProperty($folderRecordPublisher, 'requestToken');
        $reflectionProperty->setAccessible(true);

        $foreignDatabase->expects($this->once())->method('insert')->with(
            'tx_in2publishcore_filepublisher_task',
            [
                'request_token' => $reflectionProperty->getValue($folderRecordPublisher),
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'storage_uid' => $deletedFolder->getForeignProps()['storage'],
                'identifier' => $deletedFolder->getForeignProps()['combinedIdentifier'],
                'identifier_hash' => '',
                'folder_action' => $folderRecordPublisher::A_DELETE,
            ]
        );
        $folderRecordPublisher->injectForeignDatabase($foreignDatabase);

        $folderRecordPublisher->publish($deletedFolder);
    }

    /**
     * @covers ::publish
     */
    public function testPublishAddsAddedFolder()
    {
        $folderRecordPublisher = new FolderRecordPublisher();
        $foreignDatabase =  $this->createMock(Connection::class);
        $addedFolder = $this->createMock(FileRecord::class);
        $addedFolder->method('getClassification')->willReturn('_folder');
        $addedFolder->method('getState')->willReturn(Record::S_ADDED);
        $addedFolder->method('getLocalProps')->willReturn(
            ['storage' => 1, 'identifier' => 'bar', 'combinedIdentifier' => '1:bar']
        );
        $addedFolder->method('getForeignProps')->willReturn([]);

        $reflectionProperty = new \ReflectionProperty($folderRecordPublisher, 'requestToken');
        $reflectionProperty->setAccessible(true);

        $foreignDatabase->expects($this->once())->method('insert')->with(
            'tx_in2publishcore_filepublisher_task',
            [
                'request_token' => $reflectionProperty->getValue($folderRecordPublisher),
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'storage_uid' => $addedFolder->getLocalProps()['storage'],
                'identifier' => $addedFolder->getLocalProps()['combinedIdentifier'],
                'identifier_hash' => '',
                'folder_action' => $folderRecordPublisher::A_INSERT,
            ]
        );
        $folderRecordPublisher->injectForeignDatabase($foreignDatabase);

        $folderRecordPublisher->publish($addedFolder);
    }
}
