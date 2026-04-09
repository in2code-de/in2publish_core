<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Publisher;

use In2code\In2publishCore\Component\Core\Publisher\DatabaseRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\FileRecordPublisher;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\AddFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\DeleteFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\MoveFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\ReplaceAndRenameFileInstruction;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\ReplaceFileInstruction;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandResponse;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\AssetTransmitter;
use In2code\In2publishCore\Tests\Unit\Component\Core\Publisher\Constraint\IsEqualIgnoringRequestToken;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function json_encode;
use function sha1;
use function str_replace;
use function strrpos;
use function substr;
use function trim;

#[CoversMethod(FileRecordPublisher::class, '__construct')]
#[CoversMethod(FileRecordPublisher::class, 'canPublish')]
#[CoversMethod(FileRecordPublisher::class, 'publish')]
class FileRecordPublisherTest extends UnitTestCase
{
    protected Context $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = GeneralUtility::makeInstance(Context::class);
    }

    public function testCanPublishReturnsTrueForFileRecordsOnly(): void
    {
        $fileRecordPublisher = new FileRecordPublisher();

        $fileRecord = $this->createStub(FileRecord::class);
        $this->assertTrue($fileRecordPublisher->canPublish($fileRecord));

        $folderRecord = $this->createStub(FolderRecord::class);
        $this->assertFalse($fileRecordPublisher->canPublish($folderRecord));

        $databaseRecord = $this->createStub(DatabaseRecord::class);
        $this->assertFalse($fileRecordPublisher->canPublish($databaseRecord));
    }

    public function testPublishDeletesRemovedFile(): void
    {
        $fileRecordPublisher = $this->createFileRecordPublisher();

        $foreignDatabase = $this->getForeignDatabaseMock($fileRecordPublisher);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            new IsEqualIgnoringRequestToken([
                [
                    'crdate' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'tstamp' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'instruction' => DeleteFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'fileIdentifier' => '/foo/bar',
                    ]),
                ],
            ]),
        );

        $deletedFile = new FileRecord(
            [],
            $this->createFileInfo('bar', 1, '23149872364', '/foo'),
        );

        $fileRecordPublisher->publish($deletedFile);
        $fileRecordPublisher->finish();
    }

    public function testPublishTransmitsAddedRecord(): void
    {
        $fileRecordPublisher = $this->createFileRecordPublisher('/var/tmp/asdfasdf.tmp');

        $foreignDatabase = $this->getForeignDatabaseMock($fileRecordPublisher);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            new IsEqualIgnoringRequestToken([
                [
                    'crdate' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'tstamp' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'instruction' => AddFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'foreignTemporaryFileIdentifier' => '/var/tmp/asdfasdf.tmp',
                        'foreignTargetFileIdentifier' => '/foo/bar',
                    ]),
                ],
            ]),
        );

        $addedFile = new FileRecord(
            $this->createFileInfo('bar', 1, '23450978', '/foo'),
            [],
        );

        $fileRecordPublisher->publish($addedFile);
        $fileRecordPublisher->finish();
    }

    public function testPublishTransmitsRenamedRecord(): void
    {
        $fileRecordPublisher = $this->createFileRecordPublisher();

        $assetTransmitter = $this->createStub(AssetTransmitter::class);
        $fileRecordPublisher->injectAssetTransmitter($assetTransmitter);

        $changedFile = new FileRecord(
            $this->createFileInfo('bar2', 1, '23149872364', '/foo'),
            $this->createFileInfo('bar', 1, '23149872364', '/foo'),
        );

        $foreignDatabase = $this->getForeignDatabaseMock($fileRecordPublisher);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            new IsEqualIgnoringRequestToken([
                [
                    'crdate' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'tstamp' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'instruction' => MoveFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'oldFileIdentifier' => '/foo/bar',
                        'newFileIdentifier' => '/foo/bar2',
                    ]),
                ],
            ]),
        );

        $fileRecordPublisher->publish($changedFile);
        $fileRecordPublisher->finish();
    }

    public function testPublishTransmitsMovedRecord(): void
    {
        $fileRecordPublisher = $this->createFileRecordPublisher();

        $movedFile = new FileRecord(
            $this->createFileInfo('foo', 1, '452093485', '/foo'),
            $this->createFileInfo('foo', 1, '452093485', '/bar'),
        );

        $foreignDatabase = $this->getForeignDatabaseMock($fileRecordPublisher);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            new IsEqualIgnoringRequestToken([
                [
                    'crdate' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'tstamp' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'instruction' => MoveFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'oldFileIdentifier' => '/bar/foo',
                        'newFileIdentifier' => '/foo/foo',
                    ]),
                ],
            ]),
        );

        $fileRecordPublisher->publish($movedFile);
        $fileRecordPublisher->finish();
    }

    public function testPublishTransmitsReplacedFileWithNewNameRecord(): void
    {
        $fileRecordPublisher = $this->createFileRecordPublisher('/var/tmp/transient/sadsdas.tmp');

        $movedFile = new FileRecord(
            $this->createFileInfo('foo', 1, '452093485', '/foo'),
            $this->createFileInfo('bar', 1, '234587', '/bar'),
        );

        $foreignDatabase = $this->getForeignDatabaseMock($fileRecordPublisher);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            new IsEqualIgnoringRequestToken([
                [
                    'crdate' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'tstamp' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'instruction' => ReplaceAndRenameFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'oldFileIdentifier' => '/bar/bar',
                        'foreignTargetFileIdentifier' => '/foo/foo',
                        'foreignTemporaryFileIdentifier' => '/var/tmp/transient/sadsdas.tmp',
                    ]),
                ],
            ]),
        );

        $fileRecordPublisher->publish($movedFile);
        $fileRecordPublisher->finish();
    }

    public function testPublishTransmitsReplacedFileWithSameNameRecord(): void
    {
        $fileRecordPublisher = $this->createFileRecordPublisher('/var/tmp/transient/sadsdas.tmp');

        $movedFile = new FileRecord(
            $this->createFileInfo('foo', 1, '452093485', '/foo'),
            $this->createFileInfo('foo', 1, '234587', '/foo'),
        );

        $foreignDatabase = $this->getForeignDatabaseMock($fileRecordPublisher);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            new IsEqualIgnoringRequestToken([
                [
                    'crdate' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'tstamp' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                    'instruction' => ReplaceFileInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'foreignTargetFileIdentifier' => '/foo/foo',
                        'foreignTemporaryFileIdentifier' => '/var/tmp/transient/sadsdas.tmp',
                    ]),
                ],
            ]),
        );

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
        $fileRecordPublisher = new class($temporaryFileIdentifier) extends FileRecordPublisher {
            public function __construct(private readonly string $tmpFile) {}

            protected function transmitTemporaryFile(Record $record): string
            {
                return $this->tmpFile;
            }
        };
        $remoteCommandResponse = $this->createStub(RemoteCommandResponse::class);
        $remoteCommandResponse->method('isSuccessful')->willReturn(true);
        $remoteCommandDispatcher = $this->createStub(RemoteCommandDispatcher::class);
        $remoteCommandDispatcher->method('dispatch')->willReturn($remoteCommandResponse);
        $fileRecordPublisher->injectRemoteCommandDispatcher($remoteCommandDispatcher);

        return $fileRecordPublisher;
    }

    /**
     * @param DatabaseRecordPublisher $
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function getForeignDatabaseMock(FileRecordPublisher $fileRecordPublisher): MockObject
    {
        $foreignDatabase = $this->createMock(Connection::class);
        $reflection = new \ReflectionProperty(FileRecordPublisher::class, 'foreignDatabase');
        $reflection->setAccessible(true);
        $reflection->setValue($fileRecordPublisher, $foreignDatabase);
        return $foreignDatabase;
    }
}
