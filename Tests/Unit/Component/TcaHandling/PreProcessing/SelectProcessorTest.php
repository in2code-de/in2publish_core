<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\PreProcessing;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\SelectProcessor;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\DatabaseIdentifierQuotingService;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Service\ReplaceMarkersService;
use In2code\In2publishCore\Tests\UnitTestCase;

use function array_merge;

class SelectProcessorTest extends UnitTestCase
{
    public function testSelectProcessorReturnsCompatibleResultForCompatibleColumn(): void
    {
        $selectProcessor = new SelectProcessor();
        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'select',
            'foreign_table' => 'tableNameBeng',
        ]);
        $this->assertTrue($processingResult->isCompatible());
    }

    public function forbiddenTcaDataProvider(): array
    {
        return [
            [['itemsProcFunc' => '']],
            [['fileFolder' => '']],
            [['allowNonIdValues' => '']],
            [['MM_oppositeUsage' => '']],
            [['special' => '']],
        ];
    }

    /**
     * @depends      testSelectProcessorReturnsDemand
     * @dataProvider forbiddenTcaDataProvider
     */
    public function testSelectProcessorFlagsColumnsWithForbiddenTcaAsIncompatible(array $tca): void
    {
        $tca = array_merge($tca, ['type' => 'select', 'foreign_table' => 'tableNameBeng']);
        $selectProcessor = new SelectProcessor();
        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());
    }

    public function testSelectProcessorFiltersTca(): void
    {
        $bigTca = [
            'type' => 'select',
            'foreign_table' => 'tableNameBeng',
            'foreign_table_where' => '',
            'MM' => '',
            'MM_hasUidField' => '',
            'MM_match_fields' => [],
            'MM_table_where' => '',
            'rootLevel' => '',
            'filterMe' => '',
        ];
        $expectedTca = [
            'type' => 'select',
            'foreign_table' => 'tableNameBeng',
            'foreign_table_where' => '',
            'MM' => '',
            'MM_hasUidField' => '',
            'MM_match_fields' => [],
            'MM_table_where' => '',
            'rootLevel' => '',
        ];
        $selectProcessor = new SelectProcessor();
        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $bigTca);
        $this->assertSame($expectedTca, $processingResult->getValue()['tca']);
    }

    /**
     * @depends testSelectProcessorReturnsCompatibleResultForCompatibleColumn
     */
    public function testSelectProcessorCreatesDemandForSimpleRelation(): void
    {
        $tca = [
            'type' => 'select',
            'foreign_table' => 'tableNameBeng',
            'foreign_table_where' => ' AND fieldname = "fieldvalue"',
        ];
        $replaceMarkerService = $this->createMock(ReplaceMarkersService::class);
        $replaceMarkerService->method('replaceMarkers')->willReturnArgument(1);

        $diqs = $this->createMock(DatabaseIdentifierQuotingService::class);
        $diqs->method('dododo')->willReturnArgument(0);

        $selectProcessor = new SelectProcessor();
        $selectProcessor->injectReplaceMarkersService($replaceMarkerService);
        $selectProcessor->injectDatabaseIdentifierQuotingService($diqs);

        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $tca);

        $resolver = $processingResult->getValue()['resolver'];

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $databaseRecord->method('getClassification')->willReturn('tableNameFoo');
        $databaseRecord->method('getId')->willReturn(4);
        $databaseRecord->method('getProp')->willReturn('15, 56');

        $demand = $resolver($databaseRecord);

        $expectedDemand = [];
        $expectedDemand['select']['tableNameBeng']['fieldname = "fieldvalue"']['uid'][15]['tableNameFoo' . "\0" . 4] = $databaseRecord;
        $expectedDemand['select']['tableNameBeng']['fieldname = "fieldvalue"']['uid'][56]['tableNameFoo' . "\0" . 4] = $databaseRecord;

        $this->assertSame($expectedDemand, $demand);
    }

    /**
     * @depends testSelectProcessorReturnsCompatibleResultForCompatibleColumn
     */
    public function testSelectProcessorCreatesDemandForMMRelation(): void
    {
        $tca = [
            'type' => 'select',
            'foreign_table' => 'tableNameBeng',
            'foreign_table_where' => ' AND fieldname = "fieldvalue"',
            'MM' => 'tableNameFoo_tableNameBeng_MM',
            'MM_match_fields' => [
                'fieldName2' => 'fieldValue2'
            ],
        ];
        $replaceMarkerService = $this->createMock(ReplaceMarkersService::class);
        $replaceMarkerService->method('replaceMarkers')->willReturnArgument(1);

        $diqs = $this->createMock(DatabaseIdentifierQuotingService::class);
        $diqs->method('dododo')->willReturnArgument(0);

        $selectProcessor = new SelectProcessor();
        $selectProcessor->injectReplaceMarkersService($replaceMarkerService);
        $selectProcessor->injectDatabaseIdentifierQuotingService($diqs);

        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $tca);

        $resolver = $processingResult->getValue()['resolver'];

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $databaseRecord->method('getId')->willReturn(4);
        $databaseRecord->method('getClassification')->willReturn('tableNameFoo');

        $demand = $resolver($databaseRecord);

        $expectedDemand = [];
        $expectedDemand['join']['tableNameFoo_tableNameBeng_MM']['tableNameBeng']['fieldname = "fieldvalue" AND fieldName2 = "fieldValue2"']['uid_local'][4]['tableNameFoo' . "\0" . 4] = $databaseRecord;

        $this->assertSame($expectedDemand, $demand);
    }
}
