<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\InputProcessor;
use In2code\In2publishCore\Component\Core\Resolver\TextResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\InputProcessor
 */
class InputProcessorTest extends UnitTestCase
{
    /**
     * @covers ::additionalPreProcess
     */
    public function testInputProcessorProcessesFieldsWithTypolinkSoftref(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $inputProcessor = new InputProcessor();
        $inputProcessor->injectContainer($container);
        $processingResult = $inputProcessor->process('tableNameFoo', 'fieldNameBar', ['softref' => 'typolink']);
        $this->assertTrue($processingResult->isCompatible());
    }

    /**
     * @covers ::additionalPreProcess
     */
    public function testInputProcessorIgnoresFieldsWithoutSupportedSoftref(): void
    {
        $inputProcessor = new InputProcessor();
        $processingResult = $inputProcessor->process('tableNameFoo', 'fieldNameBar', ['softref' => 'asdasd']);
        $this->assertFalse($processingResult->isCompatible());
        $this->assertSame(
            ['Only input fields with typolinks can hold relations'],
            $processingResult->getValue()
        );
    }

    /**
     * @covers ::additionalPreProcess
     */
    public function testInputProcessorIgnoresFieldsWithoutSoftref(): void
    {
        $inputProcessor = new InputProcessor();
        $processingResult = $inputProcessor->process('tableNameFoo', 'fieldNameBar', []);
        $this->assertFalse($processingResult->isCompatible());
    }
}
