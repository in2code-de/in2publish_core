<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\DemandResolver\Filesystem;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Type\FoldersInFolderDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\FoldersInFolderDemandResolver;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInformationCollection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FolderInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\MissingFolderInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\ForeignFolderInfoService;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\HiddenFilesAndFoldersService;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFolderInfoService;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\FolderRecord;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

use function array_keys;
use function in_array;

#[CoversMethod(FoldersInFolderDemandResolver::class, 'resolveDemand')]
class FoldersInFolderDemandResolverTest extends UnitTestCase
{
    private const STORAGE = 1;
    private const PARENT_IDENTIFIER = '/Testcases/';
    private const VISIBLE_IDENTIFIER = '/Testcases/visible_folder/';
    private const HIDDEN_IDENTIFIER = '/Testcases/.hidden_folder/';

    public function testHiddenFoldersAreSkippedWhenTheBackendUserSettingIsDisabled(): void
    {
        $parentRecord = $this->resolveDemandWithSkippedIdentifiers([self::HIDDEN_IDENTIFIER]);

        $children = $parentRecord->getChildren()[FolderRecord::CLASSIFICATION] ?? [];
        $this->assertSame(
            [self::STORAGE . ':' . self::VISIBLE_IDENTIFIER],
            array_keys($children),
        );
    }

    public function testHiddenFoldersAreKeptWhenTheBackendUserSettingIsEnabled(): void
    {
        $parentRecord = $this->resolveDemandWithSkippedIdentifiers([]);

        $children = $parentRecord->getChildren()[FolderRecord::CLASSIFICATION] ?? [];
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
        $parentFolderInfo->addFolder(new FolderInfo(self::STORAGE, self::VISIBLE_IDENTIFIER, 'visible_folder'));
        $parentFolderInfo->addFolder(new FolderInfo(self::STORAGE, self::HIDDEN_IDENTIFIER, '.hidden_folder'));

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
        $recordFactory->method('createFolderRecord')
                      ->willReturnCallback(
                          static fn(array $localProps, array $foreignProps): FolderRecord
                              => new FolderRecord($localProps, $foreignProps),
                      );

        $resolver = new FoldersInFolderDemandResolver();
        $resolver->injectLocalFolderInfoService($localFolderInfoService);
        $resolver->injectForeignFolderInfoService($foreignFolderInfoService);
        $resolver->injectHiddenFilesAndFoldersService($hiddenFilesAndFoldersService);
        $resolver->injectRecordFactory($recordFactory);

        $parentRecord = new FolderRecord(
            ['storage' => self::STORAGE, 'identifier' => self::PARENT_IDENTIFIER, 'name' => 'Testcases'],
            [],
        );

        $demands = $this->createStub(DemandsCollection::class);
        $demands->method('getDemandsByType')->with(FoldersInFolderDemand::class)->willReturn([
            self::STORAGE => [
                self::PARENT_IDENTIFIER => [$parentRecord],
            ],
        ]);

        $resolver->resolveDemand($demands, new RecordCollection());

        return $parentRecord;
    }
}
