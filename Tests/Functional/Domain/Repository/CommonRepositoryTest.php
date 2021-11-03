<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Domain\Repository;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\PostProcessing\PostProcessingEventListener;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Event\RootRecordCreationWasFinished;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use ReflectionProperty;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function uniqid;

class CommonRepositoryTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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

    /**
     * @covers ::findByIdentifier
     * @covers ::findPropertiesByProperty
     * @covers ::enrichPageRecord
     */
    public function testPageToContentRelationViaPid()
    {
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $defaultConnection = $pool->getConnectionByName('Default');
        $defaultConnection->insert('pages', ['uid' => 1]);
        $defaultConnection->insert('tt_content', ['uid' => 4, 'pid' => 1]);

        $commonRepository = GeneralUtility::makeInstance(CommonRepository::class);
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
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $defaultConnection = $pool->getConnectionByName('Default');
        $defaultConnection->insert('tt_content', ['uid' => 13]);
        $defaultConnection->insert('sys_file_reference',
                                   [
                                       'uid' => 55,
                                       'tablenames' => 'tt_content',
                                       'fieldname' => 'media',
                                       'uid_foreign' => 13,
                                       'uid_local' => 44,
                                   ]
        );
        $defaultConnection->insert('sys_file', ['uid' => 44, 'name' => 'FooBar.file']);

        $commonRepository = GeneralUtility::makeInstance(CommonRepository::class);
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
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $defaultConnection = $pool->getConnectionByName('Default');
        $canary = uniqid('', false);
        $defaultConnection->insert('pages', ['uid' => 5, 'categories' => 1]);
        $defaultConnection->insert('sys_category', ['uid' => 2, 'items' => 1, 'title' => $canary]);
        $defaultConnection->insert(
            'sys_category_record_mm',
            [
                'uid_local' => 2,
                'uid_foreign' => 5,
                'tablenames' => 'pages',
                'fieldname' => 'categories',
            ]
        );
        // sys_category is a select-MM relation with MM_matchFields and no UID. To identify the MM-Record properly, all
        // fields which determine the identity of the entity have to be used as identifier.
        $mmRecordIdentifier = '{"uid_local":2,"uid_foreign":5,"sorting":0,"tablenames":"pages","fieldname":"categories"}';

        $commonRepository = GeneralUtility::makeInstance(CommonRepository::class);
        $record = $commonRepository->findByIdentifier(5, 'pages');

        $this->assertSame('pages', $record->getTableName());
        $this->assertSame(5, $record->getIdentifier());

        $relatedReferences = $record->getRelatedRecords();
        $this->assertArrayHasKey('sys_category_record_mm', $relatedReferences);

        $mmRecords = $relatedReferences['sys_category_record_mm'];
        $this->assertCount(1, $mmRecords);
        $this->assertArrayHasKey($mmRecordIdentifier, $mmRecords);

        $mmRecord = $mmRecords[$mmRecordIdentifier];
        $relatedCategory = $mmRecord->getRelatedRecords();
        $this->assertArrayHasKey('sys_category', $relatedCategory);

        $categories = $relatedCategory['sys_category'];
        $this->assertArrayHasKey(2, $categories);

        $category = $categories[2];
        $this->assertSame('sys_category', $category->getTableName());
        $this->assertSame(2, $category->getIdentifier());
        $this->assertSame($canary, $category->getLocalProperty('title'));
    }

    public function testSelectSingleRelationsAreResolved(): void
    {
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $defaultConnection = $pool->getConnectionByName('Default');
        $defaultConnection->insert('pages', ['uid' => 5, 'sys_language_uid' => 1]);
        $defaultConnection->insert('sys_language', ['uid' => 1]);

        $commonRepository = GeneralUtility::makeInstance(CommonRepository::class);
        $record = $commonRepository->findByIdentifier(5, 'pages');

        $relatedRecords = $record->getRelatedRecords();
        $this->assertCount(1, $relatedRecords);

        $this->assertArrayHasKey('sys_language', $relatedRecords);

        $relatedLanguages = $relatedRecords['sys_language'];
        $this->assertCount(1, $relatedLanguages);
        $this->assertArrayHasKey(1, $relatedLanguages);

        $relatedLanguage = $relatedLanguages[1];
        $this->assertInstanceOf(RecordInterface::class, $relatedLanguage);

        $this->assertSame('sys_language', $relatedLanguage->getTableName());
        $this->assertSame(1, $relatedLanguage->getIdentifier());
    }

    public function testRelatedRecordsArePublished(): void
    {
        $pool = GeneralUtility::makeInstance(ConnectionPool::class);
        $defaultConnection = $pool->getConnectionByName('Default');
        $foreignConnection = $pool->getConnectionByName('Foreign');
        $defaultConnection->insert('pages', ['uid' => 5, 'sys_language_uid' => 1]);
        $defaultConnection->insert('sys_language', ['uid' => 1]);

        $query = $foreignConnection->createQueryBuilder();
        $query->select('*')->from('sys_language');
        $result = $query->execute();
        $rows = $result->fetchAllAssociative();
        $this->assertEmpty($rows);

        $commonRepository = GeneralUtility::makeInstance(CommonRepository::class);
        $record = $commonRepository->findByIdentifier(5, 'pages');

        $commonRepository->publishRecordRecursive($record);

        $query = $foreignConnection->createQueryBuilder();
        $query->select('*')->from('sys_language');
        $result = $query->execute();
        $rows = $result->fetchAllAssociative();

        $this->assertCount(1, $rows);
        $this->assertSame(1, $rows[0]['uid']);
    }
}
