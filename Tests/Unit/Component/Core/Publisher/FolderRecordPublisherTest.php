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
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;

use function json_encode;

#[CoversMethod(FolderRecordPublisher::class, '__construct')]
#[CoversMethod(FolderRecordPublisher::class, 'canPublish')]
#[CoversMethod(FolderRecordPublisher::class, 'publish')]
class FolderRecordPublisherTest extends UnitTestCase
{
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

    public function testPublishRemovesDeletedFolder()
    {
        $folderRecordPublisher = $this->createFolderRecordPublisher();

        $deletedFolder = new FolderRecord(
            [],
            (new FolderInfo(1, '/foo/bar', 'bar'))->toArray(),
        );

        $foreignDatabase = $this->getForeignDatabaseMock($folderRecordPublisher);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            new IsEqualIgnoringRequestToken([
                [
                    'crdate' => \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('date', 'timestamp'),
                    'tstamp' => \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('date', 'timestamp'),
                    'instruction' => DeleteFolderInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'folderIdentifier' => '/foo/bar',
                    ]),
                ],
            ]),
        );

        $folderRecordPublisher->publish($deletedFolder);
        $folderRecordPublisher->finish();
    }

    public function testPublishAddsAddedFolder()
    {
        $folderRecordPublisher = $this->createFolderRecordPublisher();

        $addedFolder = new FolderRecord(
            (new FolderInfo(1, '/foo/bar', 'bar'))->toArray(),
            [],
        );

        $foreignDatabase = $this->getForeignDatabaseMock($folderRecordPublisher);
        $foreignDatabase->expects($this->once())->method('bulkInsert')->with(
            'tx_in2publishcore_filepublisher_instruction',
            new IsEqualIgnoringRequestToken([
                [
                    'crdate' => \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('date', 'timestamp'),
                    'tstamp' => \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class)->getPropertyFromAspect('date', 'timestamp'),
                    'instruction' => AddFolderInstruction::class,
                    'configuration' => json_encode([
                        'storage' => 1,
                        'folderIdentifier' => '/foo/bar',
                    ]),
                ],
            ]),
        );

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

    protected function getForeignDatabaseMock(FolderRecordPublisher $folderRecordPublisher): MockObject
    {
        $foreignDatabase = $this->createMock(Connection::class);
        $reflection = new \ReflectionProperty(FolderRecordPublisher::class, 'foreignDatabase');
        $reflection->setAccessible(true);
        $reflection->setValue($folderRecordPublisher, $foreignDatabase);
        return $foreignDatabase;
    }
}
