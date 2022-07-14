<?php

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\GroupProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Component\Core\Resolver\GroupMmMultiTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\GroupMultiTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\GroupSingleTableResolver;
use In2code\In2publishCore\Component\Core\Resolver\SelectMmResolver;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\GroupProcessor
 */
class GroupProcessorTest extends \In2code\In2publishCore\Tests\UnitTestCase
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
    }

    /**
     * @covers ::process
     */
    public function testProcessCanResolveMultipleTables(): void
    {
        $GLOBALS['TCA']['tableNameFoo'] = [];
        $GLOBALS['TCA']['tableNameBar'] = [];

        $groupProcessor = new GroupProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $groupProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $container = $this->createMock(Container::class);
        $groupResolver = $this->createMock(GroupMultiTableResolver::class);
        $container->method('get')->willReturn($groupResolver);
        $groupProcessor->injectContainer($container);

        $multiTableTca =  ['type' => 'group', 'allowed' => 'tableNameFoo,tableNameBar'];
        $processingResult = $groupProcessor->process('tableNameFoo', 'fieldNameBar', $multiTableTca);
        $this->assertTrue($processingResult->isCompatible());
        $processingResult = $groupProcessor->process('tableNameBar', 'fieldNameBar', $multiTableTca);
        $this->assertTrue($processingResult->isCompatible());
        // TODO: expected this to be incompatible
        $processingResult = $groupProcessor->process('tableNameBaz', 'fieldNameBar', $multiTableTca);
        $this->assertTrue($processingResult->isCompatible());

        // incompatible, because tableNameBaz is not contained in TCA
        $incompatibleMultiTableTca =  ['type' => 'group', 'allowed' => 'tableNameFoo,tableNameBaz'];
        $processingResult = $groupProcessor->process('tableNameFoo', 'fieldNameBar', $incompatibleMultiTableTca);
        $this->assertFalse($processingResult->isCompatible());


        $allTableTca = ['type' => 'group', 'allowed' => '*'];
        $processingResult = $groupProcessor->process('tableNameFoo', 'fieldNameBar', $allTableTca);
        $this->assertTrue($processingResult->isCompatible());
        $processingResult = $groupProcessor->process('tableNameBar', 'fieldNameBar', $allTableTca);
        $this->assertTrue($processingResult->isCompatible());
        // TODO: expected this to be incompatible
        $processingResult = $groupProcessor->process('tableNameBaz', 'fieldNameBar', $multiTableTca);
        $this->assertTrue($processingResult->isCompatible());

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

    public function tcaDataProvider(): array
    {
        return [
            [['allowed' => 'tableNameFoo,tableNameBar']],
            [['allowed' => '*']],
        ];
    }

    public function allowedTcaDataProvider(): array
    {
        return [
            [['internal_type' => '']],
            [['MM' => '']],
            [['MM_hasUidField' => '']],
            [['MM_match_fields' => '']],
            [['MM_table_where' => '']],
            [['uploadfolder' => '']],
        ];
    }
}
