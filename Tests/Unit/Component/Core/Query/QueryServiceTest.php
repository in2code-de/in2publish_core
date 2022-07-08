<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Query;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\DemandResolver\Select\SelectDemandResolver;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactory;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndex;
use In2code\In2publishCore\Component\Core\Repository\DualDatabaseRepository;
use In2code\In2publishCore\Component\Core\Repository\SingleDatabaseRepository;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\DemandResolver\Select\SelectDemandResolver
 */
class QueryServiceTest extends TestCase
{
    /**
     * @covers ::resolveDemands
     * @covers ::resolveSelectDemand
     * @covers ::findMissingRecordsByUid
     */
    public function testSelectDemandWithOnlyLocalRowsWillQueryForRowsMissingFromForeign(): void
    {
        $dualDatabaseRepository = $this->createMock(DualDatabaseRepository::class);
        $dualDatabaseRepository->expects($this->once())
                               ->method('findByProperty')
                               ->with('foo', 'pid', [6, 8], 'AND 1=1')
                               ->willReturn(
                                   [
                                       14 => [
                                           'local' => [
                                               'uid' => 14,
                                               'pid' => 6,
                                               'title' => 'foo',
                                           ],
                                           'foreign' => [],
                                           'additional' => [],
                                       ],
                                   ]
                               );
        $foreignSingleDatabaseRepository = $this->createMock(SingleDatabaseRepository::class);
        $foreignSingleDatabaseRepository->expects($this->once())
                                        ->method('findByProperty')
                                        ->with('foo', 'uid', [14]);
        $localSingleDatabaseRepository = $this->createMock(SingleDatabaseRepository::class);
        $recordFactory = $this->createMock(RecordFactory::class);
        $recordIndex = $this->createMock(RecordIndex::class);

        $queryService = $this->createTestProxy(SelectDemandResolver::class);
        $queryService->injectDualDatabaseRepository($dualDatabaseRepository);
        $queryService->injectForeignSingleDatabaseRepository($foreignSingleDatabaseRepository);
        $queryService->injectLocalSingleDatabaseRepository($localSingleDatabaseRepository);
        $queryService->injectRecordFactory($recordFactory);
        $queryService->injectRecordIndex($recordIndex);

        $record = $this->createMock(DatabaseRecord::class);

        $demands = new DemandsCollection();
        $demands->addSelect('foo', 'AND 1=1', 'pid', 6, $record);
        $demands->addSelect('foo', 'AND 1=1', 'pid', 8, $record);

        $recordCollection = new RecordCollection();
        $queryService->resolveDemand($demands, $recordCollection);
    }

    /**
     * @covers ::resolveDemands
     * @covers ::resolveSelectDemand
     * @covers ::findMissingRecordsByUid
     */
    public function testSelectDemandWithOnlyLocalRowsWillNotQueryForMissingForeignRowsWhenPropertyIsUid(): void
    {
        $dualDatabaseRepository = $this->createMock(DualDatabaseRepository::class);
        $dualDatabaseRepository->expects($this->once())
                               ->method('findByProperty')
                               ->with('foo', 'uid', [14, 19], 'AND 1=1')
                               ->willReturn(
                                   [
                                       14 => [
                                           'local' => [
                                               'uid' => 14,
                                               'title' => 'foo',
                                           ],
                                           'foreign' => [],
                                           'additional' => [],
                                       ],
                                   ]
                               );
        $foreignSingleDatabaseRepository = $this->createMock(SingleDatabaseRepository::class);
        $foreignSingleDatabaseRepository->expects($this->never())->method('findByProperty');
        $localSingleDatabaseRepository = $this->createMock(SingleDatabaseRepository::class);
        $recordFactory = $this->createMock(RecordFactory::class);
        $recordIndex = $this->createMock(RecordIndex::class);

        $queryService = $this->createTestProxy(SelectDemandResolver::class);
        $queryService->injectDualDatabaseRepository($dualDatabaseRepository);
        $queryService->injectForeignSingleDatabaseRepository($foreignSingleDatabaseRepository);
        $queryService->injectLocalSingleDatabaseRepository($localSingleDatabaseRepository);
        $queryService->injectRecordFactory($recordFactory);
        $queryService->injectRecordIndex($recordIndex);

        $record = $this->createMock(DatabaseRecord::class);

        $demands = new DemandsCollection();
        $demands->addSelect('foo', 'AND 1=1', 'uid', 14, $record);
        $demands->addSelect('foo', 'AND 1=1', 'uid', 19, $record);

        $recordCollection = new RecordCollection();
        $queryService->resolveDemand($demands, $recordCollection);
    }

    /**
     * @covers ::resolveDemands
     * @covers ::resolveSelectDemand
     * @covers ::findMissingRecordsByUid
     */
    public function testSelectDemandWithOnlyForeignRowsWillQueryForRowsMissingFromLocal(): void
    {
        $dualDatabaseRepository = $this->createMock(DualDatabaseRepository::class);
        $dualDatabaseRepository->expects($this->once())
                               ->method('findByProperty')
                               ->with('foo', 'pid', [6, 8], 'AND 1=1')
                               ->willReturn(
                                   [
                                       14 => [
                                           'local' => [
                                           ],
                                           'foreign' => [
                                               'uid' => 14,
                                               'pid' => 6,
                                               'title' => 'foo',
                                           ],
                                           'additional' => [],
                                       ],
                                   ]
                               );
        $foreignSingleDatabaseRepository = $this->createMock(SingleDatabaseRepository::class);
        $localSingleDatabaseRepository = $this->createMock(SingleDatabaseRepository::class);
        $localSingleDatabaseRepository->expects($this->once())
                                      ->method('findByProperty')
                                      ->with('foo', 'uid', [14]);
        $recordFactory = $this->createMock(RecordFactory::class);
        $recordIndex = $this->createMock(RecordIndex::class);

        $queryService = $this->createTestProxy(SelectDemandResolver::class);
        $queryService->injectDualDatabaseRepository($dualDatabaseRepository);
        $queryService->injectForeignSingleDatabaseRepository($foreignSingleDatabaseRepository);
        $queryService->injectLocalSingleDatabaseRepository($localSingleDatabaseRepository);
        $queryService->injectRecordFactory($recordFactory);
        $queryService->injectRecordIndex($recordIndex);

        $record = $this->createMock(DatabaseRecord::class);

        $demands = new DemandsCollection();
        $demands->addSelect('foo', 'AND 1=1', 'pid', 6, $record);
        $demands->addSelect('foo', 'AND 1=1', 'pid', 8, $record);

        $recordCollection = new RecordCollection();
        $queryService->resolveDemand($demands, $recordCollection);
    }

    /**
     * @covers ::resolveDemands
     * @covers ::resolveSelectDemand
     * @covers ::findMissingRecordsByUid
     */
    public function testSelectDemandWithOnlyForeignRowsWillNotQueryForMissingLocalRowsWhenPropertyIsUid(): void
    {
        $dualDatabaseRepository = $this->createMock(DualDatabaseRepository::class);
        $dualDatabaseRepository->expects($this->once())
                               ->method('findByProperty')
                               ->with('foo', 'uid', [14, 19], 'AND 1=1')
                               ->willReturn(
                                   [
                                       14 => [
                                           'local' => [],
                                           'foreign' => [
                                               'uid' => 14,
                                               'title' => 'foo',
                                           ],
                                           'additional' => [],
                                       ],
                                   ]
                               );
        $foreignSingleDatabaseRepository = $this->createMock(SingleDatabaseRepository::class);
        $localSingleDatabaseRepository = $this->createMock(SingleDatabaseRepository::class);
        $localSingleDatabaseRepository->expects($this->never())->method('findByProperty');
        $recordFactory = $this->createMock(RecordFactory::class);
        $recordIndex = $this->createMock(RecordIndex::class);

        $queryService = $this->createTestProxy(SelectDemandResolver::class);
        $queryService->injectDualDatabaseRepository($dualDatabaseRepository);
        $queryService->injectForeignSingleDatabaseRepository($foreignSingleDatabaseRepository);
        $queryService->injectLocalSingleDatabaseRepository($localSingleDatabaseRepository);
        $queryService->injectRecordFactory($recordFactory);
        $queryService->injectRecordIndex($recordIndex);

        $record = $this->createMock(DatabaseRecord::class);

        $demands = new DemandsCollection();
        $demands->addSelect('foo', 'AND 1=1', 'uid', 14, $record);
        $demands->addSelect('foo', 'AND 1=1', 'uid', 19, $record);

        $recordCollection = new RecordCollection();
        $queryService->resolveDemand($demands, $recordCollection);
    }

    /**
     * @covers ::resolveDemands
     * @covers ::resolveSelectDemand
     * @covers ::findMissingRecordsByUid
     */
    public function testSelectDemandWithOnlyForeignRowsWillFindLocalRowsByUid(): void
    {
        $record = $this->createMock(DatabaseRecord::class);
        $record->method('getClassification')->willReturn('foo');
        $record->method('getId')->willReturn(33);

        $dualDatabaseRepository = $this->createMock(DualDatabaseRepository::class);
        $dualDatabaseRepository->expects($this->once())
                               ->method('findByProperty')
                               ->with('foo', 'pid', [14, 19], 'AND 1=1')
                               ->willReturn(
                                   [
                                       14 => [
                                           'local' => [],
                                           'foreign' => [
                                               'uid' => 14,
                                               'title' => 'foreign',
                                           ],
                                           'additional' => [],
                                       ],
                                   ]
                               );
        $foreignSingleDatabaseRepository = $this->createMock(SingleDatabaseRepository::class);
        $localSingleDatabaseRepository = $this->createMock(SingleDatabaseRepository::class);
        $localSingleDatabaseRepository->expects($this->once())
                                      ->method('findByProperty')
                                      ->willReturn(
                                          [
                                              14 => [
                                                  'uid' => 14,
                                                  'title' => 'local',
                                              ],
                                          ]
                                      );
        $recordFactory = $this->createMock(RecordFactory::class);
        $recordIndex = $this->createMock(RecordIndex::class);

        $queryService = new SelectDemandResolver();
        $queryService->injectDualDatabaseRepository($dualDatabaseRepository);
        $queryService->injectForeignSingleDatabaseRepository($foreignSingleDatabaseRepository);
        $queryService->injectLocalSingleDatabaseRepository($localSingleDatabaseRepository);
        $queryService->injectRecordFactory($recordFactory);
        $queryService->injectRecordIndex($recordIndex);

        $demands = new DemandsCollection();
        $demands->addSelect('foo', 'AND 1=1', 'pid', 14, $record);
        $demands->addSelect('foo', 'AND 1=1', 'pid', 19, $record);

        $recordCollection = new RecordCollection();
        $queryService->resolveDemand($demands, $recordCollection);
    }
}
