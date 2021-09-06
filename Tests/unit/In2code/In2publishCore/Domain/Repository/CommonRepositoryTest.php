<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\In2code\In2publishCore\Domain\Repository;

use Codeception\Test\Unit;
use In2code\In2publishCore\Domain\PostProcessing\PostProcessingEventListener;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Event\RootRecordCreationWasFinished;
use In2code\In2publishCore\Tests\UnitTester;
use ReflectionProperty;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function uniqid;

/**
 * @coversDefaultClass \In2code\In2publishCore\Domain\Repository\CommonRepository
 */
class CommonRepositoryTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->setTestExtensionsToLoad(['typo3conf/ext/in2publish_core']);
        $this->tester->setUp();
        $this->tester->setUpFunctional();
        $this->tester->setupIn2publishConfig([]);
        $this->tester->buildForeignDatabaseConnection();
        $listenerProvider = GeneralUtility::makeInstance(ListenerProvider::class);
        $reflectionProperty = new ReflectionProperty(ListenerProvider::class, 'listeners');
        $reflectionProperty->setAccessible(true);
        $listener = $reflectionProperty->getValue($listenerProvider);
        foreach ($listener[RootRecordCreationWasFinished::class] as $index => $config) {
            if ($config['service'] === PostProcessingEventListener::class) {
                unset($listener[RootRecordCreationWasFinished::class][$index]);
            }
        }
        $reflectionProperty->setValue($listenerProvider, $listener);
    }

    protected function _after()
    {
        $this->tester->tearDown();
    }

    /**
     * @covers ::findByIdentifier
     * @covers ::findPropertiesByProperty
     * @covers ::enrichPageRecord
     */
    public function testPageToContentRelationViaPid()
    {
        $this->tester->haveInDatabase('pages', ['uid' => 1]);
        $this->tester->haveInDatabase('tt_content', ['uid' => 4, 'pid' => 1]);

        $commonRepository = CommonRepository::getDefaultInstance();
        $record = $commonRepository->findByIdentifier(1, 'pages');

        $this->assertSame('pages', $record->getTableName());
        $this->assertSame(1, $record->getIdentifier());

        $relatedRecord = $record->getRelatedRecords();
        $this->assertArrayHasKey('tt_content', $relatedRecord);

        $ttContentRecords = $relatedRecord['tt_content'];
        $this->assertArrayHasKey(4, $ttContentRecords);

        $ttContentRecord = $ttContentRecords[4];
        $this->assertSame('tt_content', $ttContentRecord->getTableName());
        $this->assertSame(4, $ttContentRecord->getIdentifier());
    }

    /**
     * @covers ::findByIdentifier
     * @covers ::findPropertiesByProperty
     * @covers ::enrichRecordWithRelatedRecords
     * @covers ::fetchRelatedRecordsBySelect
     * @covers ::fetchRelatedRecordsByInline
     */
    public function testContentToImageRelationViaTCA()
    {
        $this->tester->haveInDatabase('tt_content', ['uid' => 13]);
        $this->tester->haveInDatabase(
            'sys_file_reference',
            ['uid' => 55, 'tablenames' => 'tt_content', 'fieldname' => 'media', 'uid_foreign' => 13, 'uid_local' => 44]
        );
        $this->tester->haveInDatabase('sys_file', ['uid' => 44, 'name' => 'FooBar.file']);

        $commonRepository = CommonRepository::getDefaultInstance();
        $record = $commonRepository->findByIdentifier(13, 'tt_content');

        $this->assertSame('tt_content', $record->getTableName());
        $this->assertSame(13, $record->getIdentifier());

        $relatedReferences = $record->getRelatedRecords();
        $this->assertArrayHasKey('sys_file_reference', $relatedReferences);

        $references = $relatedReferences['sys_file_reference'];
        $this->assertCount(1, $references);
        $this->assertArrayHasKey(55, $references);

        $reference = $references[55];
        $this->assertSame('sys_file_reference', $reference->getTableName());
        $this->assertSame(55, $reference->getIdentifier());

        $relatedFiles = $reference->getRelatedRecords();
        $this->assertArrayHasKey('sys_file', $relatedFiles);

        $files = $relatedFiles['sys_file'];
        $this->assertArrayHasKey(44, $files);

        $file = $files[44];
        $this->assertSame('sys_file', $file->getTableName());
        $this->assertSame(44, $file->getIdentifier());
        $this->assertSame('FooBar.file', $file->getLocalProperty('name'));
    }

    /**
     * @covers ::findByIdentifier
     * @covers ::findPropertiesByProperty
     * @covers ::enrichRecordWithRelatedRecords
     * @covers ::fetchRelatedRecordsBySelect
     * @covers ::fetchRelatedRecordsByInline
     *
     * @ticket https://projekte.in2code.de/issues/38658
     */
    public function testRelationsToCategoriesAreAlwaysResolved()
    {
        $canary = uniqid();
        $this->tester->haveInDatabase('pages', ['uid' => 5, 'categories' => 1]);
        $this->tester->haveInDatabase('sys_category', ['uid' => 2, 'items' => 1, 'title' => $canary]);
        $this->tester->haveInDatabase(
            'sys_category_record_mm',
            [
                'uid_local' => 2,
                'uid_foreign' => 5,
                'tablenames' => 'pages',
                'fieldname' => 'categories',
            ]
        );

        $commonRepository = CommonRepository::getDefaultInstance();
        $record = $commonRepository->findByIdentifier(5, 'pages');

        $this->assertSame('pages', $record->getTableName());
        $this->assertSame(5, $record->getIdentifier());

        $relatedReferences = $record->getRelatedRecords();
        $this->assertArrayHasKey('sys_category_record_mm', $relatedReferences);

        $mmRecords = $relatedReferences['sys_category_record_mm'];
        $this->assertCount(1, $mmRecords);
        $this->assertArrayHasKey('2,5', $mmRecords);

        $mmRecord = $mmRecords['2,5'];
        $relatedCategory = $mmRecord->getRelatedRecords();
        $this->assertArrayHasKey('sys_category', $relatedCategory);

        $categories = $relatedCategory['sys_category'];
        $this->assertArrayHasKey(2, $categories);

        $category = $categories[2];
        $this->assertSame('sys_category', $category->getTableName());
        $this->assertSame(2, $category->getIdentifier());
        $this->assertSame($canary, $category->getLocalProperty('title'));
    }
}
