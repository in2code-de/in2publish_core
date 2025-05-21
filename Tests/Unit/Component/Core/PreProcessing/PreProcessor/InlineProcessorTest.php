<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\InlineProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Component\Core\Resolver\InlineMultiValueResolver;
use In2code\In2publishCore\Component\Core\Resolver\InlineSelectResolver;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use In2code\In2publishCore\Component\Core\Service\Config\ExcludedTablesService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use Symfony\Component\DependencyInjection\Container;

#[CoversMethod(InlineProcessor::class, 'process')]
#[CoversMethod(InlineProcessor::class, 'buildResolver')]
class InlineProcessorTest extends UnitTestCase
{
    public function testTcaMustNotContainSymmetricField(): void
    {
        $tca = ['type' => 'inline', 'foreign_table' => 'table_foo', 'symmetric_field' => 'foo'];

        $inlineResolver = $this->createMock(StaticJoinResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($inlineResolver);
        $inlineProcessor = new InlineProcessor($container);
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $inlineProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $inlineProcessor->injectExcludedTablesService($excludedTablesService);

        $processingResult = $inlineProcessor->process('table_bar', 'field_bar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue();
        $this->assertSame(
            'symmetric_field is set on the foreign side of relations, which must not be resolved',
            $reason,
        );
    }

    public function testTcaMustContainForeignTable(): void
    {
        $tca = ['type' => 'inline'];

        $inlineResolver = $this->createMock(StaticJoinResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($inlineResolver);
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $inlineProcessor = new InlineProcessor($container);
        $inlineProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $processingResult = $inlineProcessor->process('table_bar', 'field_bar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue();
        $this->assertSame('Must be set, there is no type "inline" without a foreign table', $reason);
    }

    public function testInlineSelectResolver(): void
    {
        $tca = [
            'type' => 'inline',
            'foreign_table' => 'table_foo',
            'foreign_field' => 'foreign_field_foo',
            'foreign_table_field' => 'foreign_table_field_foo',
        ];

        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $inlineResolver = $this->createMock(InlineSelectResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($inlineResolver);
        $inlineProcessor = new InlineProcessor($container);
        $inlineProcessor->injectTcaEscapingMarkerService($tcaMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $inlineProcessor->injectExcludedTablesService($excludedTablesService);

        $inlineResolver->expects($this->once())
                       ->method('configure')
                       ->with(
                           'table_foo',
                           'foreign_field_foo',
                           'foreign_table_field_foo',
                           '',
                       );
        $processingResult = $inlineProcessor->process('table_bar', 'field_bar', $tca);

        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(InlineSelectResolver::class, $inlineResolver);
    }

    public function testInlineMultiValueResolver(): void
    {
        $tca1 = [
            'type' => 'inline',
            'foreign_table' => 'table_foo',
        ];

        $tca2 = [
            'type' => 'inline',
            'foreign_table' => 'table_foo',
            'foreign_table_field' => 'foreign_table_field_foo',
        ];

        $tca3 = [
            'type' => 'inline',
            'foreign_table' => 'table_foo',
            'foreign_table_field' => 'foreign_table_field_foo',
            'foreign_match_fields' => [
                'foreign_match_field1' => 'foreign_match_value1',
                'foreign_match_field2' => 'foreign_match_value2',
            ],
        ];

        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $tcaMarkerService->expects($this->exactly(3))
                         ->method('escapeMarkedIdentifier')
                         ->willReturnOnConsecutiveCalls(
                             '',
                             '',
                             'foreign_match_field1 = "foreign_match_value1" AND foreign_match_field2 = "foreign_match_value2"',
                         );

        $inlineResolver = $this->createMock(InlineMultiValueResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($inlineResolver);
        $inlineProcessor = new InlineProcessor($container);
        $inlineProcessor->injectTcaEscapingMarkerService($tcaMarkerService);


        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $inlineProcessor->injectExcludedTablesService($excludedTablesService);

        $inlineResolver->expects($this->exactly(3))->method('configure');

        $processingResult1 = $inlineProcessor->process('table_bar', 'field_bar', $tca1);
        $this->assertTrue($processingResult1->isCompatible());
        $this->assertInstanceOf(InlineMultiValueResolver::class, $inlineResolver);

        $processingResult2 = $inlineProcessor->process('table_bar', 'field_bar', $tca2);
        $this->assertTrue($processingResult2->isCompatible());
        $this->assertInstanceOf(InlineMultiValueResolver::class, $inlineResolver);

        $processingResult2 = $inlineProcessor->process('table_bar', 'field_bar', $tca3);
        $this->assertTrue($processingResult2->isCompatible());
        $this->assertInstanceOf(InlineMultiValueResolver::class, $inlineResolver);
    }
    // TODO: test for StaticJoinResolver
}
