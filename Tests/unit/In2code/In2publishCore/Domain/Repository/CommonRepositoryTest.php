<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Tests\In2code\In2publishCore\Domain\Repository;

use Codeception\Test\Unit;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Tests\UnitTester;

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
    }

    protected function _after()
    {
        $this->tester->tearDown();
    }

    public function testSomeFeature()
    {
        $this->tester->buildForeignDatabaseConnection();
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
}
