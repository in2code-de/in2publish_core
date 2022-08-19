<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\InlineProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Component\Core\Resolver\InlineMultiValueResolver;
use In2code\In2publishCore\Component\Core\Resolver\InlineSelectResolver;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\InlineProcessor
 */
class InlineProcessorTest extends UnitTestCase
{
    /**
     * @covers ::process
     */
    public function testTcaMustNotContainSymmetricField(): void
    {
        $tca = ['type' => 'inline', 'foreign_table' => 'table_foo', 'symmetric_field' => 'foo'];

        $inlineProcessor = new InlineProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $inlineProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $container = $this->createMock(Container::class);
        $inlineResolver = $this->createMock(StaticJoinResolver::class);
        $container->method('get')->willReturn($inlineResolver);
        $inlineProcessor->injectContainer($container);

        $processingResult = $inlineProcessor->process('table_bar', 'field_bar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue()[0];
        $this->assertSame('symmetric_field is set on the foreign side of relations, which must not be resolved',
            $reason);
    }

    /**
     * @covers ::process
     */
    public function testTcaMustContainForeignTable(): void
    {
        $tca = ['type' => 'inline'];

        $inlineProcessor = new InlineProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $inlineProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $container = $this->createMock(Container::class);
        $inlineResolver = $this->createMock(StaticJoinResolver::class);
        $container->method('get')->willReturn($inlineResolver);
        $inlineProcessor->injectContainer($container);

        $processingResult = $inlineProcessor->process('table_bar', 'field_bar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue()[0];
        $this->assertSame('Must be set, there is no type "inline" without a foreign table', $reason);
    }

    /**
     * @covers ::process
     */
    public function testInlineSelectResolver(): void
    {
        $tca = [
            'type' => 'inline',
            'foreign_table' => 'table_foo',
            'foreign_field' => 'foreign_field_foo',
            'foreign_table_field' => 'foreign_table_field_foo'
        ];

        $inlineProcessor = new InlineProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $inlineProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $container = $this->createMock(Container::class);
        $inlineResolver = $this->createMock(InlineSelectResolver::class);
        $container->method('get')->willReturn($inlineResolver);
        $inlineProcessor->injectContainer($container);

        $inlineResolver->expects($this->once())->method('configure')->with('table_foo', 'foreign_field_foo',
            'foreign_table_field_foo', '');
        $processingResult = $inlineProcessor->process('table_bar', 'field_bar', $tca);

        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(InlineSelectResolver::class, $inlineResolver);
    }
    /**
     * @covers ::process
     */
    public function testInlineMultiValueResolver(): void
    {
        $tca1 = [
            'type' => 'inline',
            'foreign_table' => 'table_foo'
        ];

        $tca2 = [
            'type' => 'inline',
            'foreign_table' => 'table_foo',
            'foreign_table_field' => 'foreign_table_field_foo'
        ];

        $tca3 = [
            'type' => 'inline',
            'foreign_table' => 'table_foo',
            'foreign_table_field' => 'foreign_table_field_foo',
            'foreign_match_fields' => [
                'foreign_match_field1' => 'foreign_match_value1',
                'foreign_match_field2' => 'foreign_match_value2'
            ]
        ];

        $inlineProcessor = new InlineProcessor();
        $tcaMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $tcaMarkerService->expects($this->exactly(3))
            ->method('escapeMarkedIdentifier')
            ->withConsecutive(
                [],
                [],
                ['foreign_match_field1 = "foreign_match_value1" AND foreign_match_field2 = "foreign_match_value2"']
            )->willReturnOnConsecutiveCalls(
                '',
                '',
                'foreign_match_field1 = "foreign_match_value1" AND foreign_match_field2 = "foreign_match_value2"'
            );

        $inlineProcessor->injectTcaEscapingMarkerService($tcaMarkerService);
        $container = $this->createMock(Container::class);
        $inlineResolver = $this->createMock(InlineMultiValueResolver::class);
        $container->method('get')->willReturn($inlineResolver);
        $inlineProcessor->injectContainer($container);

        $inlineResolver->expects($this->exactly(3))
            ->method('configure')
            ->withConsecutive(
                ['table_foo', 'field_bar', null, ''],
                ['table_foo', 'field_bar', 'foreign_table_field_foo', ''],
                [
                    'table_foo',
                    'field_bar',
                    'foreign_table_field_foo',
                    'foreign_match_field1 = "foreign_match_value1" AND foreign_match_field2 = "foreign_match_value2"'
                ]
            );

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
