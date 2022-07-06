<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\SelectProcessor;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\SelectMmResolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\SelectResolver;
use In2code\In2publishCore\Domain\Service\ReplaceMarkersService;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

use function array_merge;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\SelectProcessor
 */
class SelectProcessorTest extends UnitTestCase
{
    /**
     * @covers ::process
     */
    public function testSelectProcessorReturnsCompatibleResultForCompatibleColumn(): void
    {
        $resolver = $this->createMock(SelectResolver::class);
        $tcaEscapingMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $selectProcessor = new SelectProcessor();
        $selectProcessor->injectContainer($container);
        $selectProcessor->injectTcaEscapingMarkerService($tcaEscapingMarkerService);
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
     * @depends      testSelectProcessorReturnsCompatibleResultForCompatibleColumn
     * @dataProvider forbiddenTcaDataProvider
     * @covers ::process
     */
    public function testSelectProcessorFlagsColumnsWithForbiddenTcaAsIncompatible(array $tca): void
    {
        $tca = array_merge($tca, ['type' => 'select', 'foreign_table' => 'tableNameBeng']);
        $container = $this->createMock(Container::class);

        $selectProcessor = new SelectProcessor();
        $selectProcessor->injectContainer($container);
        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());
    }

    /**
     * @covers ::process
     */
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

        $resolver = $this->createMock(SelectMmResolver::class);
        $tcaEscapingMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $selectProcessor = new SelectProcessor();
        $selectProcessor->injectContainer($container);
        $selectProcessor->injectTcaEscapingMarkerService($tcaEscapingMarkerService);
        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $bigTca);
        $this->assertSame($expectedTca, $processingResult->getValue()['tca']);
    }

    /**
     * @depends testSelectProcessorReturnsCompatibleResultForCompatibleColumn
     * @covers ::process
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

        $tcaEscapingMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $tcaEscapingMarkerService->method('escapeMarkedIdentifier')->willReturnArgument(0);

        $resolver = $this->createMock(SelectResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $selectProcessor = new SelectProcessor();
        $selectProcessor->injectContainer($container);
        $selectProcessor->injectTcaEscapingMarkerService($tcaEscapingMarkerService);

        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $tca);

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];

        $this->assertInstanceOf(SelectResolver::class, $resolver);
    }

    /**
     * @depends testSelectProcessorReturnsCompatibleResultForCompatibleColumn
     * @covers ::process
     */
    public function testSelectProcessorCreatesDemandForMMRelation(): void
    {
        $tca = [
            'type' => 'select',
            'foreign_table' => 'tableNameBeng',
            'foreign_table_where' => ' AND fieldname = "fieldvalue"',
            'MM' => 'tableNameFoo_tableNameBeng_MM',
            'MM_match_fields' => [
                'fieldName2' => 'fieldValue2',
            ],
        ];
        $replaceMarkerService = $this->createMock(ReplaceMarkersService::class);
        $replaceMarkerService->method('replaceMarkers')->willReturnArgument(1);

        $tcaEscapingMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $tcaEscapingMarkerService->method('escapeMarkedIdentifier')->willReturnArgument(0);

        $resolver = $this->createMock(SelectMmResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $selectProcessor = new SelectProcessor();
        $selectProcessor->injectContainer($container);
        $selectProcessor->injectTcaEscapingMarkerService($tcaEscapingMarkerService);

        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $tca);

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];

        $this->assertInstanceOf(SelectMmResolver::class, $resolver);
    }
}
