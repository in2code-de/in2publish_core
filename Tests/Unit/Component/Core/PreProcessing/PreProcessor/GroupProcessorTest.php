<?php

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\GroupProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Component\Core\Resolver\GroupMmMultiTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\GroupMultiTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\GroupSingleTableResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\GroupProcessor
 */
class GroupProcessorTest extends UnitTestCase
{
    /**
     * @covers ::process
     */
    public function testProcessRequiresAllowedFieldInTca(): void
    {
        $tca =  ['type' => 'group', 'allowed' => ''];

        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $container = $this->createMock(Container::class);
        $groupResolver = $this->createMock(GroupMultiTableResolver::class);
        $container->method('get')->willReturn($groupResolver);
        $groupProcessor->injectContainer($container);

        $processingResult = $groupProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());

        $tca =  ['type' => 'group'];

        $this->expectError();
        $this->expectErrorMessage('Undefined array key "allowed"');
        $groupProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
    /**
     * @covers ::process
     * @covers ::additionalPreProcess
     */
    public function testProcessInternalTypeMustBeDb(): void
    {
        $GLOBALS['TCA']['table_foo'] = [];
        $tca =  ['type' => 'group', 'allowed' => 'table_foo', 'internal_type' => 'file'];

        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $groupResolver = $this->createMock(GroupSingleTableResolver::class);
        $groupResolver->expects($this->once())->method('configure')->with('field_foo', 'table_foo');

        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('get')->with(GroupSingleTableResolver::class)->willReturn($groupResolver);
        $groupProcessor->injectContainer($container);

        $processingResult = $groupProcessor->process('table_foo', 'field_foo', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue()[0];
        $this->assertSame('The internal type "file" is not supported', $reason);

        $tca =  ['type' => 'group', 'allowed' => 'table_foo', 'internal_type' => 'db'];
        $processingResult = $groupProcessor->process('table_foo', 'field_foo', $tca);
        $this->assertTrue($processingResult->isCompatible());

        unset($GLOBALS['TCA']['table_foo']);
    }

    /**
     * @covers ::process
     * @covers ::additionalPreProcess
     */
    public function testProcessAllowedTableMustBeInTca(): void
    {
        $GLOBALS['TCA']['table_foo'] = [];
        $tca =  ['type' => 'group', 'allowed' => 'table_bar'];

        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $container = $this->createMock(Container::class);
        $groupProcessor->injectContainer($container);


        $processingResult = $groupProcessor->process('table_foo', 'field_foo', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue()[0];
        $expectedMessage = 'Can not reference the table "table_bar" from "allowed. It is not present in the TCA';
        $this->assertSame($expectedMessage, $reason);

        unset($GLOBALS['TCA']['table_foo']);
    }

    /**
     * @covers ::process
     */
    public function testTcaMustNotContainMmOppositeField(): void
    {
        $GLOBALS['TCA']['tableNameBar'] = [];

        $tca = [
            'type' => 'group',
            'allowed' => 'tableNameBar',
            'MM_opposite_field' => 'fieldBar',
        ];
        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $container = $this->createMock(Container::class);
        $groupProcessor->injectContainer($container);

        $processingResult = $groupProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue()[0];
        $this->assertSame(
            'MM_opposite_field is set for the foreign side of relations, which must not be resolved',
            $reason
        );

        unset($GLOBALS['TCA']['tableNameBar']);
    }

    /**
     * @covers ::process
     */
    public function testProcessingForSingleTableRelations(): void
    {
        $GLOBALS['TCA']['tableNameFoo'] = [];
        $GLOBALS['TCA']['tableNameBar'] = [];

        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $groupResolver = $this->createMock(GroupSingleTableResolver::class);
        $groupResolver->expects($this->once())->method('configure')->with('fieldNameBar', 'tableNameFoo');

        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('get')->with(GroupSingleTableResolver::class)->willReturn($groupResolver);
        $groupProcessor->injectContainer($container);

        $singleTableTca = ['type' => 'group', 'allowed' => 'tableNameFoo'];
        $processingResult = $groupProcessor->process('tableNameFoo', 'fieldNameBar', $singleTableTca);
        $resolver = $processingResult->getValue()['resolver'];
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupSingleTableResolver::class, $resolver);

        unset($GLOBALS['TCA']['tableNameFoo']);
        unset($GLOBALS['TCA']['tableNameBar']);
    }

    /**
     * @covers ::process
     */
    public function testProcessingForMultipleTablesRelations(): void
    {
        $GLOBALS['TCA']['tableNameFoo'] = [];
        $GLOBALS['TCA']['tableNameBar'] = [];

        $multiTableTca = ['type' => 'group', 'allowed' => 'tableNameFoo,tableNameBar'];

        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $groupResolver = $this->createMock(GroupMultiTableResolver::class);
        $groupResolver->expects($this->once())->method('configure')->with(['tableNameFoo','tableNameBar'], 'fieldNameBar');

        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('get')->with(GroupMultiTableResolver::class)->willReturn($groupResolver);
        $groupProcessor->injectContainer($container);

        $processingResult = $groupProcessor->process('tableNameBar', 'fieldNameBar', $multiTableTca);
        $resolver = $processingResult->getValue()['resolver'];
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupMultiTableResolver::class, $resolver);

        unset($GLOBALS['TCA']['tableNameFoo']);
        unset($GLOBALS['TCA']['tableNameBar']);
    }

    /**
     * @covers ::process
     */
    public function testProcessingForAllTablesRelations(): void
    {
        $GLOBALS['TCA']['tableNameBar'] = [];

        $allTableTca = ['type' => 'group', 'allowed' => '*'];

        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $groupResolver = $this->createMock(GroupMultiTableResolver::class);
        $groupResolver->expects($this->once())->method('configure')->with(['*'], 'fieldNameBar');

        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('get')->with(GroupMultiTableResolver::class)->willReturn($groupResolver);
        $groupProcessor->injectContainer($container);

        $processingResult = $groupProcessor->process('tableNameBar', 'fieldNameBar', $allTableTca);
        $resolver = $processingResult->getValue()['resolver'];
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupMultiTableResolver::class, $resolver);

        unset($GLOBALS['TCA']['tableNameBar']);
    }

    /**
     * @covers ::process
     * Case 1: no MM_match_fields
     */
    public function testProcessingResultForMmTableRelations1(): void
    {
        $GLOBALS['TCA']['tableNameBar'] = [];
        $GLOBALS['TCA']['tableNameFoo'] = [];

        $mmTableTca = [
            'type' => 'group',
            'allowed' => 'tableNameBar, tableNameFoo',
            'MM' => 'mmTable',
        ];

        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $groupResolver = $this->createMock(GroupMmMultiTableResolver::class);
        $groupResolver->expects($this->once())->method('configure')->with(['tableNameBar', 'tableNameFoo'], 'mmTable', 'fieldNameBar', 'uid_local', '');

        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('get')->with(GroupMmMultiTableResolver::class)->willReturn($groupResolver);
        $groupProcessor->injectContainer($container);

        $processingResult = $groupProcessor->process('tableNameBar', 'fieldNameBar', $mmTableTca);
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupMmMultiTableResolver::class, $groupResolver);

        unset($GLOBALS['TCA']['tableNameFoo']);
        unset($GLOBALS['TCA']['tableNameBar']);
    }

    /**
     * @covers ::process
     * Case 2: with MM_match_fields
     */
    public function testProcessingResultForMmTableRelations2(): void
    {
        $GLOBALS['TCA']['tableNameBar'] = [];
        $GLOBALS['TCA']['tableNameFoo'] = [];

        $mmTableTca = [
            'type' => 'group',
            'allowed' => 'tableNameBar, tableNameFoo',
            'MM' => 'mmTable',
            'MM_match_fields' => [
                'matchFieldFoo' => 'matchFieldBar',
                'matchFieldBaz' => 'matchFieldBeng',
            ],
        ];

        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $tcaMarkerService->expects($this->once())->method('escapeMarkedIdentifier')->with('matchFieldFoo = "matchFieldBar" AND matchFieldBaz = "matchFieldBeng"')->willReturn('matchFieldFoo = "matchFieldBar" AND matchFieldBaz = "matchFieldBeng"');
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $groupResolver = $this->createMock(GroupMmMultiTableResolver::class);
        $groupResolver->expects($this->once())->method('configure')->with(['tableNameBar', 'tableNameFoo'], 'mmTable', 'fieldNameBar', 'uid_local', 'matchFieldFoo = "matchFieldBar" AND matchFieldBaz = "matchFieldBeng"');

        $container = $this->createMock(Container::class);
        $container->expects($this->once())->method('get')->with(GroupMmMultiTableResolver::class)->willReturn($groupResolver);
        $groupProcessor->injectContainer($container);

        $processingResult = $groupProcessor->process('tableNameBar', 'fieldNameBar', $mmTableTca);
        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(GroupMmMultiTableResolver::class, $groupResolver);

        unset($GLOBALS['TCA']['tableNameFoo']);
        unset($GLOBALS['TCA']['tableNameBar']);
    }

    /**
     * @covers ::isSingleTable
     */
    public function testIsSingleTable(): void
    {
        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $reflectionMethod = new \ReflectionMethod(GroupProcessor::class, 'isSingleTable');
        $reflectionMethod->setAccessible(true);

        $allowedTables = 'table1';
        $this->assertTrue($reflectionMethod->invoke($groupProcessor, $allowedTables));

        $allowedTables = 'table1, table2';
        $this->assertFalse($reflectionMethod->invoke($groupProcessor, $allowedTables));

        $allowedTables = '*';
        $this->assertFalse($reflectionMethod->invoke($groupProcessor, $allowedTables));
    }
}
