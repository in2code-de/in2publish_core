<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\PreProcessing;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\InputProcessor;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\InputProcessor
 */
class InputProcessorTest extends UnitTestCase
{
    /**
     * @covers ::additionalPreProcess
     */
    public function testInputProcessorProcessesFieldsWithTypolinkSoftref(): void
    {
        $inputProcessor = new InputProcessor();
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
