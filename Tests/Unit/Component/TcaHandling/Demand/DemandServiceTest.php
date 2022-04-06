<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\Demand;

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandService;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\Demand\DemandService
 */
class DemandServiceTest extends UnitTestCase
{
    public function testBuildDemandForRecordsReturnsContentOfResolverArray(): void
    {
        $demandService = new DemandService();
        $record = new DatabaseRecord('table_foo', 1234, ['column_foo' => 1], []);
        $compatibleTcaParts = [
            'table_foo' => [
                'column_foo' => [
                    'resolver' => static function() { return ['select_statement_1', 'select_statement_2'];}
                ]
            ]
        ];

        $tcaProcessingService = $this->createMock(TcaPreProcessingService::class);
        $tcaProcessingService->method('getCompatibleTcaParts')->willReturn($compatibleTcaParts);
        $demandService->injectTcaPreProcessingService($tcaProcessingService);

        $demand = $demandService->buildDemandForRecords([$record]);
        $this->assertIsArray($demand);
        $this->assertSame($demand, ['select_statement_1', 'select_statement_2']);
    }

    public function testBuildDemandForRecordsReturnsResolversOfAllRecords(): void
    {
        $demandService = new DemandService();
        $record1 = new DatabaseRecord('table_foo', 1234, ['column_foo' => 1], []);
        $record2 = new DatabaseRecord('table_bar', 1234, ['column_bar' => 1], []);
        $compatibleTcaParts = [
            'table_foo' => [
                'column_foo' => [
                    'resolver' => static function($record1) { return ['select1' => 'select_statement_from_' . $record1->getTable()];}
                ]
            ],
            'table_bar' => [
                'column_bar' => [
                    'resolver' => static function($record2) { return ['select2' => 'select_statement_from_' . $record2->getTable()];}
                ]
            ]
        ];

        $tcaProcessingService = $this->createMock(TcaPreProcessingService::class);
        $tcaProcessingService->method('getCompatibleTcaParts')->willReturn($compatibleTcaParts);
        $demandService->injectTcaPreProcessingService($tcaProcessingService);

        $demand = $demandService->buildDemandForRecords([$record1, $record2]);
        $this->assertIsArray($demand);
        $this->assertEquals($demand, ['select1' =>'select_statement_from_table_foo', 'select2' =>'select_statement_from_table_bar']);
    }
}
