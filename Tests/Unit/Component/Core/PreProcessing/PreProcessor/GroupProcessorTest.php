<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\GroupProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Component\Core\Resolver\GroupMmMultiTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\GroupMultiTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\GroupSingleTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use In2code\In2publishCore\Component\Core\Service\Config\ExcludedTablesService;
use In2code\In2publishCore\Tests\UnitTestCase;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\GroupProcessor
 */
class GroupProcessorTest extends UnitTestCase
{
    /**
     * @covers ::process
     * @covers ::buildResolver
     * @covers ::additionalPreProcess
     */
    public function testProcessRequiresAllowedFieldInTca(): void
    {
        $tca = ['type' => 'group', 'allowed' => ''];

        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $container = $this->createMock(Container::class);
        $groupResolver = $this->createMock(GroupMultiTableResolver::class);
        $container->method('get')->willReturn($groupResolver);
        $groupProcessor = new GroupProcessor($container);

        $processingResult = $groupProcessor->process('table_foo', 'field_bar', $tca);
        $this->assertFalse($processingResult->isCompatible());

        $tca = ['type' => 'group'];

        $this->expectException(\Error::class);
        // Error message differs depending on PHP version
        //$this->expectErrorMessage('Undefined array key "allowed"');
        $groupProcessor->process('table_foo', 'field_bar', $tca);
    }

    /**
     * @covers ::process
     * @covers ::buildResolver
     * @covers ::additionalPreProcess
     */
    public function testProcessInternalTypeMustBeDb(): void
    {
        $GLOBALS['TCA']['table_foo'] = [];
        $tca = ['type' => 'group', 'allowed' => 'table_foo', 'internal_type' => 'file'];

        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupResolver = $this->createMock(GroupSingleTableResolver::class);

        $container = $this->createMock(Container::class);
        $container->expects($this->once())
                  ->method('get')
                  ->with(GroupSingleTableResolver::class)
                  ->willReturn($groupResolver);
        $groupProcessor = new GroupProcessor($container);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $groupResolver->expects($this->once())->method('configure')->with('field_foo', 'table_foo');

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $excludedTablesService->method('removeExcludedTables')->willReturnArgument(0);
        $groupProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $groupProcessor->process('table_foo', 'field_foo', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue();
        $this->assertSame('The internal type "file" is not supported', $reason);

        $tca = ['type' => 'group', 'allowed' => 'table_foo', 'internal_type' => 'db'];
        $processingResult = $groupProcessor->process('table_foo', 'field_foo', $tca);
        $this->assertTrue($processingResult->isCompatible());

        unset($GLOBALS['TCA']['table_foo']);
    }

    /**
     * @covers ::process
     * @covers ::buildResolver
     * @covers ::additionalPreProcess
     */
    public function testProcessAllowedTableMustBeInTca(): void
    {
        $GLOBALS['TCA']['table_foo'] = [];
        $tca = ['type' => 'group', 'allowed' => 'table_bar'];

        $groupProcessor = new GroupProcessor($this->createMock(Container::class));
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $excludedTablesService->method('removeExcludedTables')->willReturnArgument(0);
        $groupProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $groupProcessor->process('table_foo', 'field_foo', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue();
        $expectedMessage = 'Can not reference the table "table_bar" from "allowed. It is not present in the TCA';
        $this->assertSame($expectedMessage, $reason);

        unset($GLOBALS['TCA']['table_foo']);
    }

    /**
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testTcaMustNotContainMmOppositeField(): void
    {
        $GLOBALS['TCA']['table_bar'] = [];

        $tca = [
            'type' => 'group',
            'allowed' => 'table_bar',
            'MM_opposite_field' => 'field_bar',
        ];
        $groupProcessor = new GroupProcessor($this->createMock(Container::class));
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $groupProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $groupProcessor->process('table_foo', 'field_bar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue()[0];
        $this->assertSame(
            'MM_opposite_field is set for the foreign side of relations, which must not be resolved',
            $reason,
        );

        unset($GLOBALS['TCA']['table_bar']);
    }

    /**
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testProcessingForSingleTableRelations(): void
    {
        $GLOBALS['TCA']['table_foo'] = [];
        $GLOBALS['TCA']['table_bar'] = [];

        $groupResolver = $this->createMock(GroupSingleTableResolver::class);
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $container = $this->createMock(Container::class);
        $groupResolver->expects($this->once())->method('configure')->with('field_bar', 'table_foo');

        $container->expects($this->once())
                  ->method('get')
                  ->with(GroupSingleTableResolver::class)
                  ->willReturn($groupResolver);
        $groupProcessor = new GroupProcessor($container);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $excludedTablesService->method('removeExcludedTables')->willReturnArgument(0);
        $groupProcessor->injectExcludedTablesService($excludedTablesService);

        $singleTableTca = ['type' => 'group', 'allowed' => 'table_foo'];
        $processingResult = $groupProcessor->process('table_foo', 'field_bar', $singleTableTca);
        $resolver = $processingResult->getValue()['resolver'];
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupSingleTableResolver::class, $resolver);

        unset($GLOBALS['TCA']['table_foo']);
        unset($GLOBALS['TCA']['table_bar']);
    }

    /**
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testProcessingForMultipleTablesRelations(): void
    {
        $GLOBALS['TCA']['table_foo'] = [];
        $GLOBALS['TCA']['table_bar'] = [];

        $multiTableTca = ['type' => 'group', 'allowed' => 'table_foo,table_bar'];

        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupResolver = $this->createMock(GroupMultiTableResolver::class);
        $groupResolver->expects($this->once())->method('configure')->with(['table_foo', 'table_bar'], 'field_bar');

        $container = $this->createMock(Container::class);
        $container->expects($this->once())
                  ->method('get')
                  ->with(GroupMultiTableResolver::class)
                  ->willReturn($groupResolver);

        $groupProcessor = new GroupProcessor($container);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $excludedTablesService->method('removeExcludedTables')->willReturnArgument(0);
        $groupProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $groupProcessor->process('table_bar', 'field_bar', $multiTableTca);
        $resolver = $processingResult->getValue()['resolver'];
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupMultiTableResolver::class, $resolver);

        unset($GLOBALS['TCA']['table_foo']);
        unset($GLOBALS['TCA']['table_bar']);
    }

    /**
     * @covers ::process
     * @covers ::additionalPreProcess
     * @covers ::buildResolver
     */
    public function testProcessingForAllTablesRelations(): void
    {
        $GLOBALS['TCA']['table_bar'] = [];

        $allTableTca = ['type' => 'group', 'allowed' => '*'];

        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $excludedTablesService->method('removeExcludedTables')->willReturnArgument(0);
        $excludedTablesService->method('getAllNonExcludedTcaTables')->willReturn(['table_foo', 'table_bar', 'table_baz', 'pages']);

        $groupResolver = $this->createMock(GroupMultiTableResolver::class);
        $groupResolver->expects($this->once())->method('configure')->with(['table_foo', 'table_bar', 'table_baz'], 'field_bar');

        $container = $this->createMock(Container::class);
        $container->expects($this->once())
                  ->method('get')
                  ->with(GroupMultiTableResolver::class)
                  ->willReturn($groupResolver);

        $groupProcessor = new GroupProcessor($container);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $groupProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $groupProcessor->process('table_bar', 'field_bar', $allTableTca);
        $resolver = $processingResult->getValue()['resolver'];
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupMultiTableResolver::class, $resolver);

        unset($GLOBALS['TCA']['table_bar']);
    }

    /**
     * @covers ::process
     * @covers ::buildResolver
     *
     * Case 1: no MM_match_fields
     */
    public function testProcessingResultForMmTableRelations(): void
    {
        $GLOBALS['TCA']['table_bar'] = [];
        $GLOBALS['TCA']['table_foo'] = [];

        $mmTableTcaMultipleTable = [
            'type' => 'group',
            'allowed' => 'table_bar, table_foo',
            'MM' => 'mmTable',
        ];

        $mmTableTcaSingleTable = [
            'type' => 'group',
            'allowed' => 'table_bar',
            'MM' => 'mmTable',
        ];


        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);

        $groupResolver = $this->createMock(GroupMmMultiTableResolver::class);
        $groupResolver->expects($this->once())
                      ->method('configure')
                      ->with(
                          ['table_bar', 'table_foo'],
                          'mmTable',
                          'field_bar',
                          'uid_local',
                          '',
                      );

        $staticJoinResolver = $this->createMock(StaticJoinResolver::class);
        $staticJoinResolver->expects($this->once())->method('configure')->with('mmTable', 'table_bar', '', 'uid_local');

        $container = $this->createMock(Container::class);

        $container->expects($this->exactly(2))
                  ->method('get')
                  ->willReturnOnConsecutiveCalls(
                      $groupResolver,
                      $staticJoinResolver,
                  );

        $groupProcessor = new GroupProcessor($container);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $excludedTablesService->method('removeExcludedTables')->willReturnArgument(0);
        $groupProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult1 = $groupProcessor->process('table_bar', 'field_bar', $mmTableTcaMultipleTable);
        $this->assertTrue($processingResult1->isCompatible());
        $resolver = $processingResult1->getValue()['resolver'];
        $this->assertInstanceOf(GroupMmMultiTableResolver::class, $resolver);

        $processingResult = $groupProcessor->process('table_bar', 'field_bar', $mmTableTcaSingleTable);
        $this->assertTrue($processingResult->isCompatible());

        unset($GLOBALS['TCA']['table_foo']);
        unset($GLOBALS['TCA']['table_bar']);
    }

    /**
     * @covers ::process
     * @covers ::buildResolver
     *
     * Case 2a: with MM_match_fields, string match values
     */
    public function testProcessingResultForMmTableRelationsWithStringMatchFields(): void
    {
        $GLOBALS['TCA']['table_bar'] = [];
        $GLOBALS['TCA']['table_foo'] = [];

        $mmTableTcaWithStringValues = [
            'type' => 'group',
            'allowed' => 'table_bar, table_foo',
            'MM' => 'mmTable',
            'MM_match_fields' => [
                'match_field_foo' => 'match_value_foo',
                'match_field_baz' => 'match_value_baz',
            ],
        ];

        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $tcaMarkerService->expects($this->once())
                         ->method('escapeMarkedIdentifier')
                         ->with('match_field_foo = "match_value_foo" AND match_field_baz = "match_value_baz"')
                         ->willReturn('match_field_foo = "match_value_foo" AND match_field_baz = "match_value_baz"');

        $groupResolver = $this->createMock(GroupMmMultiTableResolver::class);
        $groupResolver->expects($this->once())
                      ->method('configure')
                      ->with(
                          ['table_bar', 'table_foo'],
                          'mmTable',
                          'field_bar',
                          'uid_local',
                          'match_field_foo = "match_value_foo" AND match_field_baz = "match_value_baz"',
                      );

        $container = $this->createMock(Container::class);
        $container->expects($this->once())
                  ->method('get')
                  ->with(GroupMmMultiTableResolver::class)
                  ->willReturn($groupResolver);
        $groupProcessor = new GroupProcessor($container);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $excludedTablesService->method('removeExcludedTables')->willReturnArgument(0);
        $groupProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $groupProcessor->process('table_bar', 'field_bar', $mmTableTcaWithStringValues);
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupMmMultiTableResolver::class, $groupResolver);

        unset($GLOBALS['TCA']['table_foo']);
        unset($GLOBALS['TCA']['table_bar']);
    }

    /**
     * @covers ::process
     * @covers ::buildResolver
     *
     * Case 2b: with MM_match_fields, integer match values
     */
    public function testProcessingResultForMmTableRelationsWithIntegerMatchFields(): void
    {
        $GLOBALS['TCA']['table_bar'] = [];
        $GLOBALS['TCA']['table_foo'] = [];

        $mmTableTcaWithIntegerValues = [
            'type' => 'group',
            'allowed' => 'table_bar, table_foo',
            'MM' => 'mmTable',
            'MM_match_fields' => [
                'match_field_foo' => 2,
                'match_field_baz' => 3,
            ],
        ];

        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $tcaMarkerService->expects($this->once())
                         ->method('escapeMarkedIdentifier')
                         ->with('match_field_foo = 2 AND match_field_baz = 3')
                         ->willReturn('match_field_foo = 2 AND match_field_baz = 3');

        $groupResolver = $this->createMock(GroupMmMultiTableResolver::class);
        $groupResolver->expects($this->once())
                      ->method('configure')
                      ->with(
                          ['table_bar', 'table_foo'],
                          'mmTable',
                          'field_bar',
                          'uid_local',
                          'match_field_foo = 2 AND match_field_baz = 3',
                      );

        $container = $this->createMock(Container::class);
        $container->expects($this->once())
                  ->method('get')
                  ->with(GroupMmMultiTableResolver::class)
                  ->willReturn($groupResolver);
        $groupProcessor = new GroupProcessor($container);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $excludedTablesService->method('removeExcludedTables')->willReturnArgument(0);
        $groupProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $groupProcessor->process('table_bar', 'field_bar', $mmTableTcaWithIntegerValues);
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupMmMultiTableResolver::class, $groupResolver);

        unset($GLOBALS['TCA']['table_foo']);
        unset($GLOBALS['TCA']['table_bar']);
    }

    /**
     * @covers ::isSingleTable
     */
    public function testIsSingleTable(): void
    {
        $groupProcessor = new GroupProcessor($this->createMock(Container::class));
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $reflectionMethod = new ReflectionMethod(GroupProcessor::class, 'isSingleTable');
        $reflectionMethod->setAccessible(true);

        $allowedTables = 'table1';
        $this->assertTrue($reflectionMethod->invoke($groupProcessor, $allowedTables));

        $allowedTables = 'table1, table2';
        $this->assertFalse($reflectionMethod->invoke($groupProcessor, $allowedTables));

        $allowedTables = '*';
        $this->assertFalse($reflectionMethod->invoke($groupProcessor, $allowedTables));
    }
}
