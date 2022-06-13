<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\Demand;

use In2code\In2publishCore\Component\TcaHandling\Demand\Demands;
use In2code\In2publishCore\Component\TcaHandling\Demand\DemandsCollection;
use In2code\In2publishCore\Component\TcaHandling\Demand\DemandService;
use In2code\In2publishCore\Component\TcaHandling\Demand\DemandsFactory;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use In2code\In2publishCore\Component\TcaHandling\Service\ResolverService;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\Demand\DemandService
 */
class DemandServiceTest extends UnitTestCase
{
    /**
     * @covers ::buildDemandForRecords
     */
    public function testBuildDemandForRecordsReturnsContentOfResolverArray(): void
    {
        $demandsFactory = $this->createMock(DemandsFactory::class);
        $demandsFactory->method('buildDemand')->willReturn(new DemandsCollection());

        $demandService = new DemandService();
        $demandService->injectDemandsFactory($demandsFactory);
        $record = new DatabaseRecord('table_foo', 1234, ['column_foo' => 1], [], []);
        $resolversForTable = [
            'column_foo' => new class implements Resolver {
                public function getTargetTables(): array
                {
                    return [];
                }

                public function resolve(Demands $demands, Record $record): void
                {
                    $demands->addSelect('foo', 'bar', 'baz', 'beng', $record);
                }
            }
        ];

        $resolverService = $this->createMock(ResolverService::class);
        $resolverService->method('getResolversForTable')->willReturn($resolversForTable);
        $demandService->injectResolverService($resolverService);

        $demand = $demandService->buildDemandForRecords(new RecordCollection([$record]));
        $this->assertInstanceOf(Demands::class, $demand);
        $expected = [];
        $expected['foo']['bar']['baz']['beng']['table_foo\\1234'] = $record;
        $this->assertSame($expected, $demand->getSelect());
    }

    /**
     * @covers ::buildDemandForRecords
     */
    public function testBuildDemandForRecordsReturnsResolversOfAllRecords(): void
    {
        $demandsFactory = $this->createMock(DemandsFactory::class);
        $demandsFactory->method('buildDemand')->willReturn(new DemandsCollection());

        $demandService = new DemandService();
        $demandService->injectDemandsFactory($demandsFactory);
        $record1 = new DatabaseRecord('table_foo', 1234, ['column_foo' => 1], [], []);
        $record2 = new DatabaseRecord('table_bar', 1234, ['column_bar' => 1], [], []);
        $resolversForTableFoo = [
            'column_foo' => new class implements Resolver {
                public function getTargetTables(): array
                {
                    return [];
                }

                public function resolve(Demands $demands, Record $record): void
                {
                    $demands->addSelect('foo', 'bar', 'baz', 'beng', $record);
                }
            },
        ];
        $resolversForTableBar = [
            'column_bar' => new class implements Resolver {
                public function getTargetTables(): array
                {
                    return [];
                }

                public function resolve(Demands $demands, Record $record): void
                {
                    $demands->addSelect('foo', 'bar', 'baz', 'beng', $record);
                }
            },
        ];

        $resolverService = $this->createMock(ResolverService::class);
        $resolverService->method('getResolversForTable')->willReturnOnConsecutiveCalls(
            $resolversForTableFoo,
            $resolversForTableBar
        );
        $demandService->injectResolverService($resolverService);

        $demand = $demandService->buildDemandForRecords(new RecordCollection([$record1, $record2]));
        $this->assertInstanceOf(Demands::class, $demand);

        $expected = [];
        $expected['foo']['bar']['baz']['beng']['table_foo\\1234'] = $record1;
        $expected['foo']['bar']['baz']['beng']['table_bar\\1234'] = $record2;

        $this->assertEquals($expected, $demand->getSelect());
    }
}
