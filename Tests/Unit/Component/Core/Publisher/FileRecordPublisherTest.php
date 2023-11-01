<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\Publisher\FileRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\AddFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\DeleteFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\MoveFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\ReplaceAndRenameFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\ReplaceFileInstruction;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandResponse;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\AssetTransmitter;
use TYPO3\CMS\Core\Database\Connection;

use function json_encode;
use function sha1;
use function str_replace;
use function strrpos;
use function substr;
use function trim;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Publisher\FileRecordPublisher
 */
class FileRecordPublisherTest extends AbstractFilesystemPublisherTest
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
        $fileRecordPublisher = $this->createFileRecordPublisher();

        $foreignDatabase = $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            [
                [
                    'request_token' => $this->getRequestTokenFromPublisher($fileRecordPublisher),
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'instruction' => DeleteFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'fileIdentifier' => '/foo/bar',
                    ]),
                ],
            ],
        );
        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);

        $deletedFile = new FileRecord(
            [],
            $this->createFileInfo('bar', 1, '23149872364', '/foo'),
        );

        $fileRecordPublisher->publish($deletedFile);
        $fileRecordPublisher->finish();
    }

    /**
     * @covers ::publish
     */
    public function testPublishTransmitsAddedRecord()
    {
        $fileRecordPublisher = $this->createFileRecordPublisher('/var/tmp/asdfasdf.tmp');

        $foreignDatabase = $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            [
                [
                    'request_token' => $this->getRequestTokenFromPublisher($fileRecordPublisher),
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'instruction' => AddFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'foreignTemporaryFileIdentifier' => '/var/tmp/asdfasdf.tmp',
                        'foreignTargetFileIdentifier' => '/foo/bar',
                    ]),
                ],
            ],
        );
        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);

        $addedFile = new FileRecord(
            $this->createFileInfo('bar', 1, '23450978', '/foo'),
            [],
        );

        $fileRecordPublisher->publish($addedFile);
        $fileRecordPublisher->finish();
    }

    /**
     * @covers ::publish
     */
    public function testPublishTransmitsRenamedRecord()
    {
        $fileRecordPublisher = $this->createFileRecordPublisher();

        $assetTransmitter = $this->createMock(AssetTransmitter::class);
        $fileRecordPublisher->injectAssetTransmitter($assetTransmitter);

        $changedFile = new FileRecord(
            $this->createFileInfo('bar2', 1, '23149872364', '/foo'),
            $this->createFileInfo('bar', 1, '23149872364', '/foo'),
        );

        $foreignDatabase = $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            [
                [
                    'request_token' => $this->getRequestTokenFromPublisher($fileRecordPublisher),
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'instruction' => MoveFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'oldFileIdentifier' => '/foo/bar',
                        'newFileIdentifier' => '/foo/bar2',
                    ]),
                ],
            ],
        );
        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);

        $fileRecordPublisher->publish($changedFile);
        $fileRecordPublisher->finish();
    }

    /**
     * @covers ::publish
     */
    public function testPublishTransmitsMovedRecord()
    {
        $fileRecordPublisher = $this->createFileRecordPublisher();

        $movedFile = new FileRecord(
            $this->createFileInfo('foo', 1, '452093485', '/foo'),
            $this->createFileInfo('foo', 1, '452093485', '/bar'),
        );

        $foreignDatabase = $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            [
                [
                    'request_token' => $this->getRequestTokenFromPublisher($fileRecordPublisher),
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'instruction' => MoveFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'oldFileIdentifier' => '/bar/foo',
                        'newFileIdentifier' => '/foo/foo',
                    ]),
                ],
            ],
        );
        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);

        $fileRecordPublisher->publish($movedFile);
        $fileRecordPublisher->finish();
    }

    /**
     * @covers ::publish
     */
    public function testPublishTransmitsReplacedFileWithNewNameRecord()
    {
        $fileRecordPublisher = $this->createFileRecordPublisher('/var/tmp/transient/sadsdas.tmp');

        $movedFile = new FileRecord(
            $this->createFileInfo('foo', 1, '452093485', '/foo'),
            $this->createFileInfo('bar', 1, '234587', '/bar'),
        );

        $foreignDatabase = $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            [
                [
                    'request_token' => $this->getRequestTokenFromPublisher($fileRecordPublisher),
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'instruction' => ReplaceAndRenameFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'oldFileIdentifier' => '/bar/bar',
                        'foreignTargetFileIdentifier' => '/foo/foo',
                        'foreignTemporaryFileIdentifier' => '/var/tmp/transient/sadsdas.tmp',
                    ]),
                ],
            ],
        );
        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);

        $fileRecordPublisher->publish($movedFile);
        $fileRecordPublisher->finish();
    }

    /**
     * @covers ::publish
     */
    public function testPublishTransmitsReplacedFileWithSameNameRecord()
    {
        $fileRecordPublisher = $this->createFileRecordPublisher('/var/tmp/transient/sadsdas.tmp');

        $movedFile = new FileRecord(
            $this->createFileInfo('foo', 1, '452093485', '/foo'),
            $this->createFileInfo('foo', 1, '234587', '/foo'),
        );

        $foreignDatabase = $this->createMock(Connection::class);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            [
                [
                    'request_token' => $this->getRequestTokenFromPublisher($fileRecordPublisher),
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'instruction' => ReplaceFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'foreignTargetFileIdentifier' => '/foo/foo',
                        'foreignTemporaryFileIdentifier' => '/var/tmp/transient/sadsdas.tmp',
                    ]),
                ],
            ],
        );
        $fileRecordPublisher->injectForeignDatabase($foreignDatabase);

        $fileRecordPublisher->publish($movedFile);
        $fileRecordPublisher->finish();
    }

    protected function createFileInfo(
        string $name,
        int $storage,
        string $hash,
        string $folder,
        string $mimeType = 'image/jpeg',
        int $size = 12345
    ): array {
        return [
            'size' => $size,
            'mimetype' => $mimeType,
            'name' => $name,
            'extension' => strrpos($name, '.') ? substr($name, strrpos($name, '.')) : '',
            'folder_hash' => sha1($folder),
            'identifier' => str_replace('//', '/', '/' . trim($folder, '/') . '/') . $name,
            'storage' => $storage,
            'sha1' => $hash,
            'publicUrl' => 'fileadmin/' . $name,
        ];
    }

    protected function createFileRecordPublisher(string $temporaryFileIdentifier = '_undefined_'): FileRecordPublisher
    {
        $fileRecordPublisher = $this->createPartialMock(FileRecordPublisher::class, ['transmitTemporaryFile']);
        $fileRecordPublisher->__construct();
        $remoteCommandResponse = $this->createMock(RemoteCommandResponse::class);
        $remoteCommandResponse->method('isSuccessful')->willReturn(true);
        $remoteCommandDispatcher = $this->createMock(RemoteCommandDispatcher::class);
        $remoteCommandDispatcher->method('dispatch')->willReturn($remoteCommandResponse);
        $fileRecordPublisher->injectRemoteCommandDispatcher($remoteCommandDispatcher);

        $fileRecordPublisher->method('transmitTemporaryFile')->willReturn($temporaryFileIdentifier);

        return $fileRecordPublisher;
    }
}
