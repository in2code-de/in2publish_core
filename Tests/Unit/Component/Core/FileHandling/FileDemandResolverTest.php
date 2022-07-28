<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\FileHandling;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\FileHandling\FileDemandResolver;
use In2code\In2publishCore\Component\Core\FileHandling\Service\ForeignFileInfoService;
use In2code\In2publishCore\Component\Core\FileHandling\Service\LocalFileInfoService;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Tests\UnitTestCase;

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

        $localFileInfoService = $this->createMock(LocalFileInfoService::class);
        $localFileInfoService->expects($this->once())->method('addFileInfoToFiles')->willReturn(
           [
               42 => [
                   'file1' => [
                       '_file//4711' => $file1,
                       'props' => [
                           'storage' => 42,
                           'identifier' =>  '_file//4711',
                           'identifier_hash' => hash('sha1',  '_file//4711'),
                           'size' => 123,
                           'mimetype' => 'some_mimetype',
                           'name' =>'some_name',
                           'extension' => 'some_extension',
                           'folder_hash' => 'some_folder_hash',
                       ]
                   ],
                   'file2' => [
                       '_file//4712' => $file2,
                       'props' => [
                           'storage' => 42,
                           'identifier' =>  '_file//4712',
                           'identifier_hash' => hash('sha1',  '_file//4712'),
                           'size' => 123,
                           'mimetype' => 'some_mimetype',
                           'name' =>'some_name',
                           'extension' => 'some_extension',
                           'folder_hash' => 'some_folder_hash',
                       ]
                   ],
               ]
           ]
        );

        $foreignFileInfoService = $this->createMock(ForeignFileInfoService::class);
        $recordFactory = $this->createMock(RecordFactory::class);
        $recordFactory->method('createFileRecord')->willReturnOnConsecutiveCalls(
            $file1,
            $file2
        );

        $fileDemandResolver->injectLocalFileInfoService($localFileInfoService);
        $fileDemandResolver->injectForeignFileInfoService($foreignFileInfoService);
        $fileDemandResolver->injectRecordFactory($recordFactory);

        $demands = $this->createMock(DemandsCollection::class);

        $demands->method('getFiles')->willReturn([
            42 => [
                'file1' => [
                    '_file//4711' => $file1
                ],
                'file2' => [
                    '_file//4712' => $file2
                ],
            ],
        ]);
        $fileDemandResolver->resolveDemand($demands);

        // TODO: check if the fulfillment of these assertions really makes sense
        $file1Children = $file1->getChildren();
        $this->assertSame($file1, $file1Children['_file']['42:file1']);

        $file2Children = $file2->getChildren();
        $this->assertSame($file2, $file2Children['_file']['42:file2']);
    }
}
