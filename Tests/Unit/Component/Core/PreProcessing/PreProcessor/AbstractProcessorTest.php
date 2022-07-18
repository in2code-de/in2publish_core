<?php

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class AbstractProcessorTest extends \In2code\In2publishCore\Tests\UnitTestCase
{
    /**
     * @covers ::process
     */
    public function testProcessingResultIsImcompatibleIfThereAreForbiddenKeysInTca(): void
    {
        $abstractProcessor = $this->getMockAbstractProcessor();

        $forbidden = new \ReflectionProperty(AbstractProcessor::class, 'forbidden');
        $forbidden->setAccessible(true);
        $forbidden->setValue($abstractProcessor, ['forbidden_key_1' => 'first_reason', 'forbidden_key_2' => 'second_reason']);

        $tca = ['type' => 'inline', 'forbidden_key_1' => 'foo'];

        $processingResult = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue()[0];
        $this->assertSame('first_reason', $reason);

        $tca = ['type' => 'inline', 'forbidden_key_2' => 'foo'];
        $processingResult = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $reason = $processingResult->getValue()[0];
        $this->assertFalse($processingResult->isCompatible());
        $this->assertSame('second_reason', $reason);
    }

    /**
     * @covers ::process
     */
    public function testProcessingResultIsIncompatibleIfRequiredFieldIsMissing(): void
    {
        $abstractProcessor = $this->getMockAbstractProcessor();

        $required = new \ReflectionProperty(AbstractProcessor::class, 'required');
        $required->setAccessible(true);
        $required->setValue($abstractProcessor, ['key_1' => 'Key 1 is required', 'key_2' => 'Key 2 is required']);

        $tca = ['type' => 'inline'];

        $processingResult = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());

        $tca = ['type' => 'inline', 'key_1' => 'foo'];
        $processingResult = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue()[0];
        $this->assertSame('Key 2 is required', $reason);

        $tca = ['type' => 'inline', 'key_2' => 'foo'];
        $processingResult = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue()[0];
        $this->assertSame('Key 1 is required', $reason);
    }

    protected function getMockAbstractProcessor(): MockObject
    {
        $tcaProcessingService = $this->createMock(TcaPreProcessingService::class);
        $container = $this->createMock(ContainerInterface::class);
        $abstractProcessor = $this->getMockForAbstractClass(
            AbstractProcessor::class);
        $abstractProcessor->setTcaPreProcessingService($tcaProcessingService);
        $abstractProcessor->injectContainer($container);
        $abstractProcessor->method('buildResolver')->willReturn($this->createMock(StaticJoinResolver::class));
        return $abstractProcessor;
    }
}
