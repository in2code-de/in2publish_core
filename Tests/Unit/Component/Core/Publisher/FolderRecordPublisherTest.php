<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FolderInfo;
use In2code\In2publishCore\Component\Core\Publisher\FolderRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\AddFolderInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\DeleteFolderInstruction;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandResponse;
use In2code\In2publishCore\Tests\Unit\Component\Core\Publisher\Constraint\IsEqualIgnoringRequestToken;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Database\Connection;

use function json_encode;

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
        $folderRecordPublisher = $this->createFolderRecordPublisher();

        $deletedFolder = new FolderRecord(
            [],
            (new FolderInfo(1, '/foo/bar', 'bar'))->toArray(),
        );

        $foreignDatabase = $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            new IsEqualIgnoringRequestToken([
                [
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'instruction' => DeleteFolderInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'folderIdentifier' => '/foo/bar',
                    ]),
                ],
            ]),
        );
        $folderRecordPublisher->injectForeignDatabase($foreignDatabase);

        $folderRecordPublisher->publish($deletedFolder);
        $folderRecordPublisher->finish();
    }

    /**
     * @covers ::publish
     */
    public function testPublishAddsAddedFolder()
    {
        $folderRecordPublisher = $this->createFolderRecordPublisher();

        $addedFolder = new FolderRecord(
            (new FolderInfo(1, '/foo/bar', 'bar'))->toArray(),
            [],
        );

        $foreignDatabase = $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            new IsEqualIgnoringRequestToken([
                [
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'instruction' => AddFolderInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'folderIdentifier' => '/foo/bar',
                    ]),
                ],
            ]),
        );
        $folderRecordPublisher->injectForeignDatabase($foreignDatabase);

        $folderRecordPublisher->publish($addedFolder);
        $folderRecordPublisher->finish();
    }

    /**
     * @return FolderRecordPublisher
     */
    public function createFolderRecordPublisher(): FolderRecordPublisher
    {
        $folderRecordPublisher = new FolderRecordPublisher();
        $remoteCommandResponse = $this->createMock(RemoteCommandResponse::class);
        $remoteCommandResponse->method('isSuccessful')->willReturn(true);
        $remoteCommandDispatcher = $this->createMock(RemoteCommandDispatcher::class);
        $remoteCommandDispatcher->method('dispatch')->willReturn($remoteCommandResponse);
        $folderRecordPublisher->injectRemoteCommandDispatcher($remoteCommandDispatcher);
        return $folderRecordPublisher;
    }
}
