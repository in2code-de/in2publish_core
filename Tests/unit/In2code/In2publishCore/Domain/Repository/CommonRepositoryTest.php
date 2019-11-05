<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Tests\In2code\In2publishCore\Domain\Repository;

use Codeception\Test\Unit;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\PostProcessing\FalIndexPostProcessor;
use In2code\In2publishCore\Domain\PostProcessing\FileIndexPostProcessor;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Tests\UnitTester;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

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
        $dispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $reflectionProperty = new \ReflectionProperty(Dispatcher::class, 'slots');
        $reflectionProperty->setAccessible(true);
        $slots = $reflectionProperty->getValue($dispatcher);
        foreach ($slots[RecordFactory::class]['instanceCreated'] as $index => $config) {
            if ($config['class'] === FalIndexPostProcessor::class) {
                unset($slots[RecordFactory::class]['instanceCreated'][$index]);
            } elseif ($config['class'] === FileIndexPostProcessor::class) {
                unset($slots[RecordFactory::class]['instanceCreated'][$index]);
            }
        }
        $reflectionProperty->setValue($dispatcher, $slots);
    }

    protected function _after()
    {
        $this->tester->tearDown();
    }

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
}
