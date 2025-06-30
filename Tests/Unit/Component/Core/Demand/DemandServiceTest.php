<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Demand;

use In2code\In2publishCore\Component\Core\Demand\DemandBuilder;
use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactory;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Service\ResolverService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(DemandBuilder::class, 'buildDemandForRecords')]
class DemandServiceTest extends UnitTestCase
{
    public function testBuildDemandForRecordsReturnsContentOfResolverArray(): void
    {
        $demandsFactory = $this->createMock(DemandsFactory::class);
        $demandsFactory->method('createDemand')->willReturn(new DemandsCollection());

        $demandBuilder = new DemandBuilder();
        $demandBuilder->injectDemandsFactory($demandsFactory);
        $record = new DatabaseRecord('table_foo', 1234, ['column_foo' => 1], [], []);
        $resolversForTable = [
            'column_foo' => new class implements Resolver {
                public function getTargetTables(): array
                {
                    return [];
                }

                public function resolve(Demands $demands, Record $record): void
                {
                    $demands->addDemand(new SelectDemand('foo', 'bar', 'baz', 'beng', $record));
                }
            },
        ];

        $resolverService = $this->createMock(ResolverService::class);
        $resolverService->method('getResolversForClassification')->willReturn($resolversForTable);
        $demandBuilder->injectResolverService($resolverService);

        $demand = $demandBuilder->buildDemandForRecords(new RecordCollection([$record]));
        $this->assertInstanceOf(Demands::class, $demand);
        $expected = [];
        $expected['foo']['bar']['baz']['beng']['table_foo\\1234'] = $record;
        $this->assertSame($expected, $demand->getDemandsByType(SelectDemand::class));
    }

    public function testBuildDemandForRecordsReturnsResolversOfAllRecords(): void
    {
        $demandsFactory = $this->createMock(DemandsFactory::class);
        $demandsFactory->method('createDemand')->willReturn(new DemandsCollection());

        $demandBuilder = new DemandBuilder();
        $demandBuilder->injectDemandsFactory($demandsFactory);
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
                    $demands->addDemand(new SelectDemand('foo', 'bar', 'baz', 'beng', $record));
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
                    $demands->addDemand(new SelectDemand('foo', 'bar', 'baz', 'beng', $record));
                }
            },
        ];

        $resolverService = $this->createMock(ResolverService::class);
        $resolverService->method('getResolversForClassification')->willReturnOnConsecutiveCalls(
            $resolversForTableFoo,
            $resolversForTableBar,
        );
        $demandBuilder->injectResolverService($resolverService);

        $demand = $demandBuilder->buildDemandForRecords(new RecordCollection([$record1, $record2]));
        $this->assertInstanceOf(Demands::class, $demand);

        $expected = [];
        $expected['foo']['bar']['baz']['beng']['table_foo\\1234'] = $record1;
        $expected['foo']['bar']['baz']['beng']['table_bar\\1234'] = $record2;

        $this->assertEquals($expected, $demand->getDemandsByType(SelectDemand::class));
    }
}
