<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\RecordTree;

use In2code\In2publishCore\Component\Core\Demand\DemandBuilder;
use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactory;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\PageTreeRootRecord;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndex;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuilder;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuildRequest;
use In2code\In2publishCore\Component\Core\Service\RelevantTablesService;
use In2code\In2publishCore\Service\Configuration\PageTypeService;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[CoversMethod(RecordTreeBuilder::class, 'findRecordsByTca')]
#[CoversMethod(RecordTreeBuilder::class, 'findAllRecordsOnPages')]
#[CoversMethod(RecordTreeBuilder::class, 'buildRecordTree')]
#[CoversMethod(RecordTreeBuilder::class, 'findRequestedRecordWithTranslations')]
class RecordTreeBuilderTest extends UnitTestCase
{
    public function testFindRecordsByTca(): void
    {
        $recordTreeBuilder = new RecordTreeBuilder();
        $record = new DatabaseRecord('table_foo', 33, [], [], []);
        $recordCollection = new RecordCollection([$record]);

        $demandResolver = $this->createMock(DemandResolver::class);
        $demandResolver->expects(self::once())->method('resolveDemand');

        $demandBuilder = $this->createMock(DemandBuilder::class);
        $demandBuilder->expects(self::once())->method('buildDemandForRecords')->with($recordCollection);

        $recordTreeBuilder->injectDemandResolver($demandResolver);
        $recordTreeBuilder->injectDemandBuilder($demandBuilder);

        $recordTreeBuilder->findRecordsByTca($recordCollection);
    }

    public function testFindAllRecordsOnPages(): void
    {
        // arrange
        $recordTreeBuilder = new RecordTreeBuilder();
        $pageRecord = new DatabaseRecord('pages', 42, ['doktype' => 1], [], []);

        $fooRecord = new DatabaseRecord('table_foo', 33, ['pid' => 42], [], []);

        $demandResolver = $this->createMock(DemandResolver::class);
        $demandResolver->expects(self::once())->method('resolveDemand')->willReturnCallback(
            function (Demands $demands, RecordCollection $recordCollection) use ($fooRecord) {
                $recordCollection->addRecord($fooRecord);
            },
        );

        $demandsFactory = $this->createMock(DemandsFactory::class);
        $demandsFactory->expects(self::once())->method('createDemand');

        $relevantTablesService = $this->createMock(RelevantTablesService::class);
        $relevantTablesService->method('getAllNonEmptyNonExcludedTcaTables')->willReturn([
            0 => 'pages',
            1 => 'table_foo',
        ]);

        $pageTypeService = $this->createMock(PageTypeService::class);
        $pageTypeService->method('getTablesAllowedOnPage')->willReturn(['table_foo']);

        $recordTreeBuilder->injectDemandResolver($demandResolver);
        $recordTreeBuilder->injectDemandsFactory($demandsFactory);
        $recordTreeBuilder->injectRelevantTablesService($relevantTablesService);
        $recordTreeBuilder->injectPageTypeService($pageTypeService);

        $recordCollection = new RecordCollection([$pageRecord, $fooRecord]);

        // act
        $recordTreeBuilder->findAllRecordsOnPages($recordCollection);

        // assert
        $pageRecordsInCollection = $recordCollection->getRecords('pages');
        $this->assertEquals($pageRecord, $pageRecordsInCollection[42]);

        $fooRecordsInCollection = $recordCollection->getRecords('table_foo');
        $this->assertEquals($fooRecord, $fooRecordsInCollection[33]);
    }

    public function testBuildRecordTreeForRootRecord(): void
    {
        $recordTreeBuilder = new RecordTreeBuilder();

        $recordFactory = $this->createMock(RecordFactory::class);
        $recordIndex = $this->createMock(RecordIndex::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $demandsFactory = $this->createMock(DemandsFactory::class);
        $demandsResolver = $this->createMock(DemandResolver::class);
        $localDatabase = $this->createMock(Connection::class);

        $recordTreeBuilder->injectRecordFactory($recordFactory);
        $recordTreeBuilder->injectRecordIndex($recordIndex);
        $recordTreeBuilder->injectEventDispatcher($eventDispatcher);
        $recordTreeBuilder->injectDemandsFactory($demandsFactory);
        $recordTreeBuilder->injectDemandResolver($demandsResolver);
        $recordTreeBuilder->injectLocalDatabase($localDatabase);

        $request = $this->createMock(RecordTreeBuildRequest::class);
        $request->method('getTable')->willReturn('pages');
        $request->method('getId')->willReturn(0);

        $recordTree = $recordTreeBuilder->buildRecordTree($request);
        $this->assertInstanceOf(RecordTree::class, $recordTree);
        $children = $recordTree->getChildren();
        $this->assertCount(1, $children);
        foreach ($children as $child) {
            foreach ($child as $childChild) {
                $this->assertInstanceOf(PageTreeRootRecord::class, $childChild);
            }
        }
    }
}
