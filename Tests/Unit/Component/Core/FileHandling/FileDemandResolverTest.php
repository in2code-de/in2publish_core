<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\FileHandling;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\FileHandling\FileDemandResolver;
use In2code\In2publishCore\Component\Core\FileHandling\Service\FileSystemInfoService;
use In2code\In2publishCore\Component\Core\FileHandling\Service\ForeignFileSystemInfoService;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Tests\UnitTestCase;

use function hash;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\FileHandling\FileDemandResolver
 */
class FileDemandResolverTest extends UnitTestCase
{

    /**
     * @covers ::resolveDemand
     */
    public function testResolveDemand(): void
    {
        $fileDemandResolver = new FileDemandResolver();
        $file1 = new FileRecord(['identifier' => 'file1', 'storage' => 42],[]);
        $file2 = new FileRecord(['identifier' => 'file2', 'storage' => 42],[]);

        $filesArray = [
            42 => [
                'foo/bar' => [$file1],
                '/file.txt' => [$file2],
            ],
        ];
        $fileInfoArray = $filesArray;
        $fileInfoArray[42]['foo/bar'] = [
            'size' => 123,
            'mimetype' => 'some_mimetype',
            'name' =>'some_name',
            'extension' => 'some_extension',
            'folder_hash' => 'some_folder_hash',
        ];
        $fileInfoArray[42]['/file.txt'] = [
            'size' => 123,
            'mimetype' => 'some_mimetype',
            'name' =>'some_name',
            'extension' => 'some_extension',
            'folder_hash' => 'some_folder_hash',
        ];

        $fileSystemInfoService = $this->createMock(FileSystemInfoService::class);
        $fileSystemInfoService->expects($this->once())->method('getFileInfo')->willReturn($fileInfoArray);

        $foreignFileSystemInfoService = $this->createMock(ForeignFileSystemInfoService::class);

        $recordFactory = $this->createMock(RecordFactory::class);
        $localProps = $fileInfoArray[42]['foo/bar'];
        $localProps['storage'] = 42;
        $localProps['identifier'] = 'foo/bar';
        $localProps['identifier_hash'] = hash('sha1', 'foo/bar');
        $fileRecordChild1 = new FileRecord($localProps, []);

        $localProps = $fileInfoArray[42]['/file.txt'];
        $localProps['storage'] = 42;
        $localProps['identifier'] = '/file.txt';
        $localProps['identifier_hash'] = hash('sha1', '/file.txt');
        $fileRecordChild2 = new FileRecord($localProps, []);
        $recordFactory->method('createFileRecord')->willReturnOnConsecutiveCalls(
            $fileRecordChild1,
            $fileRecordChild2,
        );

        $fileDemandResolver->injectFileSystemInfoService($fileSystemInfoService);
        $fileDemandResolver->injectForeignFileSystemInfoService($foreignFileSystemInfoService);
        $fileDemandResolver->injectRecordFactory($recordFactory);

        $demands = $this->createMock(DemandsCollection::class);

        $demands->method('getFiles')->willReturn($filesArray);
        $fileDemandResolver->resolveDemand($demands);

        $file1Children = $file1->getChildren();
        $this->assertSame($fileRecordChild1, $file1Children['_file']['42:foo/bar']);

        $file2Children = $file2->getChildren();
        $this->assertSame($fileRecordChild2, $file2Children['_file']['42:/file.txt']);
    }
}
