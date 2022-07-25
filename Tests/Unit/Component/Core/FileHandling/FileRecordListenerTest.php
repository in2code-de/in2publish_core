<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\FileHandling;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactory;
use In2code\In2publishCore\Component\Core\FileHandling\FileDemandResolver;
use In2code\In2publishCore\Component\Core\FileHandling\FileRecordListener;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Event\RecordWasCreated;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\FileHandling\FileRecordListener
 */
class FileRecordListenerTest extends UnitTestCase
{
    /**
     * @covers ::onRecordRelationsWereResolved
     */
    public function testOnRecordRelationsWereResolvedAddsAndResolvesOneFileDemandPerFile(): void
    {
        $fileRecordListener = new FileRecordListener();

        $fileRecords = new \ReflectionProperty($fileRecordListener, 'fileRecords');
        $fileRecords->setAccessible(true);

        $file1 = new FileRecord(['identifier' => 'file1', 'storage' => 42,],[]);
        $file2 = new FileRecord(['identifier' => 'file2', 'storage' => 42,],[]);

        $fileRecords->setValue($fileRecordListener, [$file1, $file2]);

        $demandCollection = $this->createMock(DemandsCollection::class);
        $demandCollection->expects($this->exactly(2))->method('addFile');

        $demandFactory = $this->createMock(DemandsFactory::class);
        $demandFactory->expects($this->once())->method('createDemand')->willReturn($demandCollection);

        $fileDemandResolver = $this->createMock(FileDemandResolver::class);
        $fileDemandResolver->expects($this->once())->method('resolveDemand');

        $fileRecordListener->injectFileDemandResolver($fileDemandResolver);
        $fileRecordListener->injectDemandsFactory($demandFactory);

        $fileRecordListener->onRecordRelationsWereResolved();
    }

    /**
     * @covers ::onRecordWasCreated
     */
    public function testOnRecordWasCreatedAddsFileRecordToFileRecords(): void
    {
        $fileRecordListener = new FileRecordListener();

        $fileRecords = new \ReflectionProperty($fileRecordListener, 'fileRecords');
        $fileRecords->setAccessible(true);

        $file1 = new FileRecord(['identifier' => 'file1', 'storage' => 42,],[]);
        $recordWasCreated = new RecordWasCreated($file1);

        $fileRecordListener->onRecordWasCreated($recordWasCreated);

        $this->assertEquals([$file1], $fileRecords->getValue($fileRecordListener));
    }

}
