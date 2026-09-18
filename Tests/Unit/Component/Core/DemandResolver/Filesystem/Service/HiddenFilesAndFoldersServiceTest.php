<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\DemandResolver\Filesystem\Service;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\HiddenFilesAndFoldersService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use TYPO3\CMS\Core\Resource\Filter\FileNameFilter;

#[CoversMethod(HiddenFilesAndFoldersService::class, 'isHidden')]
#[CoversMethod(HiddenFilesAndFoldersService::class, 'showHiddenFilesAndFolders')]
#[CoversMethod(HiddenFilesAndFoldersService::class, 'shouldBeSkipped')]
class HiddenFilesAndFoldersServiceTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        // FileNameFilter keeps the flag in a static property, which would leak into other tests.
        FileNameFilter::setShowHiddenFilesAndFolders(false);
        unset($GLOBALS['BE_USER']);
        parent::tearDown();
    }

    public static function identifierDataProvider(): array
    {
        return [
            'file in root' => ['/file.txt', false],
            'file in folder' => ['/Testcases/file.txt', false],
            'processed folder' => ['/_processed_/', false],
            'dot file in root' => ['/.htaccess', true],
            'dot file in folder' => ['/Testcases/.DS_Store', true],
            'dot folder' => ['/.hidden_folder/', true],
            'file in dot folder' => ['/.hidden_folder/file.txt', true],
            'dot folder in folder' => ['/Testcases/.git/', true],
            'file in nested dot folder' => ['/Testcases/.git/config', true],
        ];
    }

    #[DataProvider('identifierDataProvider')]
    public function testIsHidden(string $identifier, bool $expected): void
    {
        $service = new HiddenFilesAndFoldersService();

        $this->assertSame($expected, $service->isHidden($identifier));
    }

    #[DataProvider('identifierDataProvider')]
    public function testShouldBeSkippedDependsOnTheBackendUserSetting(string $identifier, bool $isHidden): void
    {
        FileNameFilter::setShowHiddenFilesAndFolders(false);
        $this->assertSame($isHidden, (new HiddenFilesAndFoldersService())->shouldBeSkipped($identifier));

        FileNameFilter::setShowHiddenFilesAndFolders(true);
        $this->assertFalse((new HiddenFilesAndFoldersService())->shouldBeSkipped($identifier));
    }

    public function testShowHiddenFilesAndFoldersReflectsTheFileNameFilterState(): void
    {
        $service = new HiddenFilesAndFoldersService();
        $this->assertFalse($service->showHiddenFilesAndFolders());

        FileNameFilter::setShowHiddenFilesAndFolders(true);
        $this->assertTrue((new HiddenFilesAndFoldersService())->showHiddenFilesAndFolders());
    }

    public function testShowHiddenFilesAndFoldersIsResolvedOnlyOnce(): void
    {
        $service = new HiddenFilesAndFoldersService();
        $this->assertFalse($service->showHiddenFilesAndFolders());

        FileNameFilter::setShowHiddenFilesAndFolders(true);

        $this->assertFalse($service->showHiddenFilesAndFolders());
    }
}
