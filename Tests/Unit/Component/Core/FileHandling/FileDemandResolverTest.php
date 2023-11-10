<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\FileHandling;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Type\FileDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\FileDemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FileInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInformationCollection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\ForeignFileInfoService;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFileInfoService;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Tests\UnitTestCase;

use function bin2hex;
use function hash;
use function random_bytes;
use function sha1;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\FileDemandResolver
 */
class FileDemandResolverTest extends UnitTestCase
{
    /**
     * @covers ::resolveDemand
     */
    public function testResolveDemand(): void
    {
        $fileDemandResolver = new FileDemandResolver();

        $childFile1Info = new FileInfo(
            42,
            'foo/bar',
            'some_name',
            sha1(bin2hex(random_bytes(15))),
            null,
            123,
            'some_mimetype',
            'some_extension',
            'some_folder_hash',
            sha1('foo/bar')
        );
        $childFile2Info = new FileInfo(
            42,
            '/file.txt',
            'some_name',
            sha1(bin2hex(random_bytes(15))),
            null,
            123,
            'some_mimetype',
            'some_extension',
            'some_folder_hash',
            sha1('foo/bar')
        );
        $fileInfoArray = new FilesystemInformationCollection();
        $fileInfoArray->addFilesystemInfo($childFile1Info);
        $fileInfoArray->addFilesystemInfo($childFile2Info);

        $fileSystemInfoService = $this->createMock(LocalFileInfoService::class);
        $fileSystemInfoService->expects($this->once())->method('getFileInfo')->willReturn($fileInfoArray);

        $foreignFileInfoService = $this->createMock(ForeignFileInfoService::class);

        $fileRecordChild1 = new FileRecord($childFile1Info->toArray(), []);
        $fileRecordChild2 = new FileRecord($childFile2Info->toArray(), []);
        $recordFactory = $this->createMock(RecordFactory::class);
        $recordFactory->method('createFileRecord')->willReturnOnConsecutiveCalls(
            $fileRecordChild1,
            $fileRecordChild2,
        );

        $fileDemandResolver->injectLocalFileInfoService($fileSystemInfoService);
        $fileDemandResolver->injectForeignFileInfoService($foreignFileInfoService);
        $fileDemandResolver->injectRecordFactory($recordFactory);

        $demands = $this->createMock(DemandsCollection::class);

        $parentFile1 = new FileRecord(['identifier' => 'file1', 'storage' => 42], []);
        $parentFile2 = new FileRecord(['identifier' => 'file2', 'storage' => 42], []);
        $fileDemand = [
            42 => [
                'foo/bar' => [$parentFile1],
                '/file.txt' => [$parentFile2],
            ],
        ];
        $demands->method('getDemandsByType')->with(FileDemand::class)->willReturn($fileDemand);
        $recordCollection = new RecordCollection();
        $fileDemandResolver->resolveDemand($demands, $recordCollection);

        $file1Children = $parentFile1->getChildren();
        $this->assertSame($fileRecordChild1, $file1Children['_file']['42:foo/bar']);

        $file2Children = $parentFile2->getChildren();
        $this->assertSame($fileRecordChild2, $file2Children['_file']['42:/file.txt']);
    }
}
