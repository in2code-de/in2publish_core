<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\DemandResolver\Filesystem;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactory;
use In2code\In2publishCore\Component\Core\Demand\Type\FilesInFolderDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\FilesInFolderDemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FileInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInformationCollection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FolderInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\MissingFolderInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\ForeignFolderInfoService;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\HiddenFilesAndFoldersService;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFolderInfoService;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndex;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuilder;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

use function array_keys;
use function in_array;
use function sha1;

#[CoversMethod(FilesInFolderDemandResolver::class, 'resolveDemand')]
#[CoversMethod(FilesInFolderDemandResolver::class, 'getAndProcessFilesystemInformation')]
class FilesInFolderDemandResolverTest extends UnitTestCase
{
    private const STORAGE = 1;
    private const PARENT_IDENTIFIER = '/Testcases/';
    private const VISIBLE_IDENTIFIER = '/Testcases/visible.txt';
    private const HIDDEN_IDENTIFIER = '/Testcases/.hidden.txt';

    public function testHiddenFilesAreSkippedWhenTheBackendUserSettingIsDisabled(): void
    {
        $parentRecord = $this->resolveDemandWithSkippedIdentifiers([self::HIDDEN_IDENTIFIER]);

        $children = $parentRecord->getChildren()[FileRecord::CLASSIFICATION] ?? [];
        $this->assertSame(
            [self::STORAGE . ':' . self::VISIBLE_IDENTIFIER],
            array_keys($children),
        );
    }

    public function testHiddenFilesAreKeptWhenTheBackendUserSettingIsEnabled(): void
    {
        $parentRecord = $this->resolveDemandWithSkippedIdentifiers([]);

        $children = $parentRecord->getChildren()[FileRecord::CLASSIFICATION] ?? [];
        $this->assertSame(
            [
                self::STORAGE . ':' . self::VISIBLE_IDENTIFIER,
                self::STORAGE . ':' . self::HIDDEN_IDENTIFIER,
            ],
            array_keys($children),
        );
    }

    /**
     * @param array<string> $skippedIdentifiers
     */
    private function resolveDemandWithSkippedIdentifiers(array $skippedIdentifiers): FolderRecord
    {
        $parentFolderInfo = new FolderInfo(self::STORAGE, self::PARENT_IDENTIFIER, 'Testcases');
        $parentFolderInfo->addFile($this->createFileInfo(self::VISIBLE_IDENTIFIER, 'visible.txt'));
        $parentFolderInfo->addFile($this->createFileInfo(self::HIDDEN_IDENTIFIER, '.hidden.txt'));

        $localCollection = new FilesystemInformationCollection();
        $localCollection->addFilesystemInfo($parentFolderInfo);

        $localFolderInfoService = $this->createStub(LocalFolderInfoService::class);
        $localFolderInfoService->method('getFolderInfo')->willReturn($localCollection);

        // The folder does not exist on foreign, which is what the foreign service reports back.
        $foreignCollection = new FilesystemInformationCollection();
        $foreignCollection->addFilesystemInfo(new MissingFolderInfo(self::STORAGE, self::PARENT_IDENTIFIER));

        $foreignFolderInfoService = $this->createStub(ForeignFolderInfoService::class);
        $foreignFolderInfoService->method('getFolderInformation')->willReturn($foreignCollection);

        $hiddenFilesAndFoldersService = $this->createStub(HiddenFilesAndFoldersService::class);
        $hiddenFilesAndFoldersService->method('shouldBeSkipped')
                                     ->willReturnCallback(
                                         static fn(string $identifier): bool
                                             => in_array($identifier, $skippedIdentifiers, true),
                                     );

        $recordFactory = $this->createStub(RecordFactory::class);
        $recordFactory->method('createFileRecord')
                      ->willReturnCallback(
                          static fn(array $localProps, array $foreignProps): FileRecord
                              => new FileRecord($localProps, $foreignProps),
                      );

        $demandsFactory = $this->createStub(DemandsFactory::class);
        $demandsFactory->method('createDemand')->willReturnCallback(
            static fn(): DemandsCollection => new DemandsCollection(),
        );

        $parentRecord = new FolderRecord(
            ['storage' => self::STORAGE, 'identifier' => self::PARENT_IDENTIFIER, 'name' => 'Testcases'],
            [],
        );

        $recordIndex = $this->createStub(RecordIndex::class);
        $recordIndex->method('getRecord')->willReturn($parentRecord);

        $resolver = new FilesInFolderDemandResolver();
        $resolver->injectLocalFolderInfoService($localFolderInfoService);
        $resolver->injectForeignFolderInfoService($foreignFolderInfoService);
        $resolver->injectHiddenFilesAndFoldersService($hiddenFilesAndFoldersService);
        $resolver->injectRecordFactory($recordFactory);
        $resolver->injectDemandsFactory($demandsFactory);
        $resolver->injectDemandResolver($this->createStub(DemandResolver::class));
        $resolver->injectRecordTreeBuilder($this->createStub(RecordTreeBuilder::class));
        $resolver->injectRecordIndex($recordIndex);

        $demands = $this->createStub(DemandsCollection::class);
        $demands->method('getDemandsByType')->with(FilesInFolderDemand::class)->willReturn([
            self::STORAGE => [
                self::PARENT_IDENTIFIER => [$parentRecord],
            ],
        ]);

        $resolver->resolveDemand($demands, new RecordCollection());

        return $parentRecord;
    }

    private function createFileInfo(string $identifier, string $name): FileInfo
    {
        return new FileInfo(
            self::STORAGE,
            $identifier,
            $name,
            sha1($identifier),
            null,
            123,
            'text/plain',
            'txt',
            sha1(self::PARENT_IDENTIFIER),
            sha1($identifier),
        );
    }
}
