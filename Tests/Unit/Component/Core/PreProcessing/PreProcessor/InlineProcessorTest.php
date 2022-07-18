<?php

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\InlineProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
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

        $processingResult = $inlineProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
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

        $processingResult = $inlineProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
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
        $processingResult = $inlineProcessor->process('tableNameBar', 'fieldNameBar', $tca);

        $this->assertTrue($processingResult->isCompatible());
        $this->assertInstanceOf(InlineSelectResolver::class, $inlineResolver);
    }
    // TODO: tests for InlineMultiValueResolver and StaticJoinResolver
}
