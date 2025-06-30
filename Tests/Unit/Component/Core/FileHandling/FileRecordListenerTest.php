<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\FileHandling;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactory;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\FileDemandResolver;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Event\RecordWasCreated;
use In2code\In2publishCore\Features\ResolveFilesForIndices\EventListener\FileRecordListener;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use ReflectionProperty;

#[CoversMethod(FileRecordListener::class, 'onRecordRelationsWereResolved')]
#[CoversMethod(FileRecordListener::class, 'onRecordWasCreated')]

class FileRecordListenerTest extends UnitTestCase
{
    public function testOnRecordRelationsWereResolvedAddsAndResolvesOneFileDemandPerFile(): void
    {
        $fileRecordListener = new FileRecordListener();

        $fileRecords = new ReflectionProperty($fileRecordListener, 'fileRecords');
        $fileRecords->setAccessible(true);

        $file1 = new FileRecord(['identifier' => 'file1', 'storage' => 42,], []);
        $file2 = new FileRecord(['identifier' => 'file2', 'storage' => 42,], []);

        $fileRecords->setValue($fileRecordListener, [$file1, $file2]);

        $demandCollection = $this->createMock(DemandsCollection::class);
        $demandCollection->expects($this->exactly(2))->method('addDemand');

        $demandFactory = $this->createMock(DemandsFactory::class);
        $demandFactory->expects($this->once())->method('createDemand')->willReturn($demandCollection);

        $fileDemandResolver = $this->createMock(FileDemandResolver::class);
        $fileDemandResolver->expects($this->once())->method('resolveDemand');

        $fileRecordListener->injectDemandResolver($fileDemandResolver);
        $fileRecordListener->injectDemandsFactory($demandFactory);

        $fileRecordListener->onRecordRelationsWereResolved();
    }

    public function testOnRecordWasCreatedAddsSysFileRecordToFileRecords(): void
    {
        $fileRecordListener = new FileRecordListener();

        $fileRecords = new ReflectionProperty($fileRecordListener, 'fileRecords');
        $fileRecords->setAccessible(true);

        $sysFileRecord = new DatabaseRecord('sys_file', 1, [], [], []);
        $recordWasCreated = new RecordWasCreated($sysFileRecord);

        $fileRecordListener->onRecordWasCreated($recordWasCreated);

        $this->assertEquals([$sysFileRecord], $fileRecords->getValue($fileRecordListener));
    }
}
