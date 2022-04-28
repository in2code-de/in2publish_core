<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\Demand;

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandService;
use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Component\TcaHandling\RecordCollection;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\Demand\DemandService
 */
class DemandServiceTest extends UnitTestCase
{
    public function testBuildDemandForRecordsReturnsContentOfResolverArray(): void
    {
        $demandService = new DemandService();
        $record = new DatabaseRecord('table_foo', 1234, ['column_foo' => 1], [], []);
        $compatibleTcaParts = [
            'table_foo' => [
                'column_foo' => [
                    'resolver' => new class implements Resolver {
                        public function resolve(Demands $demands, Record $record): void
                        {
                            $demands->addSelect('foo', 'bar', 'baz', 'beng', $record);
                        }
                    },
                ],
            ],
        ];

        $tcaProcessingService = $this->createMock(TcaPreProcessingService::class);
        $tcaProcessingService->method('getCompatibleTcaParts')->willReturn($compatibleTcaParts);
        $demandService->injectTcaPreProcessingService($tcaProcessingService);

        $demand = $demandService->buildDemandForRecords(new RecordCollection([$record]));
        $this->assertInstanceOf(Demands::class, $demand);
        $expected = [];
        $expected['foo']['bar']['baz']['beng']['table_foo' . "\0" . 1234] = $record;
        $this->assertSame($expected, $demand->getSelect());
    }

    public function testBuildDemandForRecordsReturnsResolversOfAllRecords(): void
    {
        $demandService = new DemandService();
        $record1 = new DatabaseRecord('table_foo', 1234, ['column_foo' => 1], [], []);
        $record2 = new DatabaseRecord('table_bar', 1234, ['column_bar' => 1], [], []);
        $compatibleTcaParts = [
            'table_foo' => [
                'column_foo' => [
                    'resolver' => new class implements Resolver {
                        public function resolve(Demands $demands, Record $record): void
                        {
                            $demands->addSelect('foo', 'bar', 'baz', 'beng', $record);
                        }
                    },
                ],
            ],
            'table_bar' => [
                'column_bar' => [
                    'resolver' => new class implements Resolver {
                        public function resolve(Demands $demands, Record $record): void
                        {
                            $demands->addSelect('foo', 'bar', 'baz', 'beng', $record);
                        }
                    },
                ],
            ],
        ];

        $tcaProcessingService = $this->createMock(TcaPreProcessingService::class);
        $tcaProcessingService->method('getCompatibleTcaParts')->willReturn($compatibleTcaParts);
        $demandService->injectTcaPreProcessingService($tcaProcessingService);

        $demand = $demandService->buildDemandForRecords(new RecordCollection([$record1, $record2]));
        $this->assertInstanceOf(Demands::class, $demand);

        $expected = [];
        $expected['foo']['bar']['baz']['beng']['table_foo' . "\0" . 1234] = $record1;
        $expected['foo']['bar']['baz']['beng']['table_bar' . "\0" . 1234] = $record2;

        $this->assertEquals($expected, $demand->getSelect());
    }
}
