<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\SelectProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\SelectMmResolver;
use In2code\In2publishCore\Component\Core\Resolver\SelectResolver;
use In2code\In2publishCore\Component\Core\Service\Config\ExcludedTablesService;
use In2code\In2publishCore\Service\ReplaceMarkersService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Container;

use function array_merge;

#[CoversMethod(SelectProcessor::class, 'process')]
#[CoversMethod(SelectProcessor::class, 'buildResolver')]
#[CoversMethod(SelectProcessor::class, 'isSysCategoryField')]
class SelectProcessorTest extends UnitTestCase
{
    public function testSelectProcessorReturnsCompatibleResultForCompatibleColumn(): void
    {
        $resolver = $this->createMock(SelectResolver::class);
        $tcaEscapingMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $selectProcessor = new SelectProcessor($container);
        $selectProcessor->injectTcaEscapingMarkerService($tcaEscapingMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $selectProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'select',
            'foreign_table' => 'tableNameBeng',
        ]);
        $this->assertTrue($processingResult->isCompatible());
    }

    public static function forbiddenTcaDataProvider(): array
    {
        return [
            [['itemsProcFunc' => '']],
            [['fileFolder' => '']],
            [['allowNonIdValues' => '']],
            [['MM_oppositeUsage' => '']],
            [['special' => '']],
        ];
    }

    #[DataProvider('forbiddenTcaDataProvider')]
    #[Depends('testSelectProcessorReturnsCompatibleResultForCompatibleColumn')]
    public function testSelectProcessorFlagsColumnsWithForbiddenTcaAsIncompatible(array $tca): void
    {
        $tca = array_merge($tca, ['type' => 'select', 'foreign_table' => 'tableNameBeng']);
        $selectProcessor = new SelectProcessor($this->createMock(Container::class));

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $selectProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());
    }

    public function testSelectProcessorFiltersTca(): void
    {
        $tca = [
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
        $processorTca = [
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

        $selectProcessor = new SelectProcessor($container);
        $selectProcessor->injectTcaEscapingMarkerService($tcaEscapingMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $selectProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertSame($processorTca, $processingResult->getValue()['tca']);
    }

    #[Depends('testSelectProcessorReturnsCompatibleResultForCompatibleColumn')]
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

        $selectProcessor = new SelectProcessor($container);
        $selectProcessor->injectTcaEscapingMarkerService($tcaEscapingMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $selectProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $tca);

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];

        $this->assertInstanceOf(SelectResolver::class, $resolver);
    }

    #[Depends('testSelectProcessorReturnsCompatibleResultForCompatibleColumn')]
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

        $selectProcessor = new SelectProcessor($container);
        $selectProcessor->injectTcaEscapingMarkerService($tcaEscapingMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $selectProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $selectProcessor->process('tableNameFoo', 'fieldNameBar', $tca);

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];

        $this->assertInstanceOf(SelectMmResolver::class, $resolver);
    }

    #[DataProvider('isSysCategoryFieldDataProvider')]
    public function testIsSysCategoryField($config, $expectation): void
    {
        $selectProcessor = new SelectProcessor($this->createMock(Container::class));
        // access protected method isSysCategoryField()
        $reflectionMethod = new ReflectionMethod(SelectProcessor::class, 'isSysCategoryField');
        $reflectionMethod->setAccessible(true);
        $isSysCategoryField = $reflectionMethod->invoke($selectProcessor, $config);

        $this->assertEquals($expectation, $isSysCategoryField);
    }

    public static function isSysCategoryFieldDataProvider(): array
    {
        return [
            'isSysCategoryField' => [
                [
                    'foreign_table' => 'sys_category',
                    'MM_opposite_field' => 'items',
                    'MM' => 'sys_category_record_mm',
                ],
                true,
            ],
            'isNotSysCategoryField1' => [
                [
                    'foreign_table' => 'not_sys_category',
                    'MM_opposite_field' => 'not_items',
                    'MM' => 'not_sys_category_record_mm',
                ],
                false,
            ],
            'missingConfig1' => [
                [
                    'foreign_table' => 'not_sys_category',
                    'MM_opposite_field' => 'not_items',
                ],
                false,
            ],
            'missingConfig2' => [
                [
                    'MM_opposite_field' => 'not_items',
                    'MM' => 'not_sys_category_record_mm',
                ],
                false,
            ],
            'missingConfig3' => [
                [
                    'foreign_table' => 'not_sys_category',
                    'MM' => 'not_sys_category_record_mm',
                ],
                false,
            ],
        ];
    }
}
