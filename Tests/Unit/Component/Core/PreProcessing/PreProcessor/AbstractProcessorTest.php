<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\Exception\MissingPreProcessorTypeException;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor
 */
class AbstractProcessorTest extends UnitTestCase
{
    /**
     * @covers ::process
     * @covers ::buildResolver
     * @covers ::getImportantFields
     */
    public function testProcessingResultIsImcompatibleIfThereAreForbiddenKeysInTca(): void
    {
        $abstractProcessor = $this->getMockAbstractProcessor();

        $forbidden = new ReflectionProperty(AbstractProcessor::class, 'forbidden');
        $forbidden->setAccessible(true);
        $forbidden->setValue(
            $abstractProcessor,
            ['forbidden_key_1' => 'first_reason', 'forbidden_key_2' => 'second_reason'],
        );

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

        $required = new ReflectionProperty(AbstractProcessor::class, 'required');
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

    /**
     * @covers ::process
     */
    public function testExceptionIsThrownIfTypeIsMissing(): void
    {
        $abstractProcessor = $this->getMockAbstractProcessor();
        // type is not set
        $this->expectExceptionObject(new MissingPreProcessorTypeException($abstractProcessor));
        $this->expectExceptionCode(1649243375);
        $abstractProcessor->getType();

        // type is set to 'processor_type'
        $abstractProcessor->method('getType')->willReturn('processor_type');
        $this->assertSame('processor_type', $abstractProcessor->getType());
    }

    /**
     * @covers ::process
     */
    public function testProcessingResultIsIncompatibleIfNoResolverIsFound(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $abstractProcessor = $this->getMockForAbstractClass(AbstractProcessor::class);
        $abstractProcessor->injectContainer($container);
        $abstractProcessor->method('buildResolver')->willReturn(null);

        $tca = ['type' => 'inline'];
        $result = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $reason = $result->getValue()[0];
        $this->assertFalse($result->isCompatible());
        $this->assertSame(
            'The processor did not return a valid resolver. The target table might be excluded or empty.',
            $reason,
        );
    }

    /**
     * @covers ::getImportantFields
     */
    public function testMethodGetImportantFields(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $abstractProcessor = $this->getMockForAbstractClass(AbstractProcessor::class);
        $abstractProcessor->injectContainer($container);

        $requiredFields = new ReflectionProperty(AbstractProcessor::class, 'required');
        $requiredFields->setAccessible(true);
        $requiredFields->setValue($abstractProcessor, ['key_1' => 'Key 1 is required', 'key_2' => 'Key 2 is required']);

        $allowedFields = new ReflectionProperty(AbstractProcessor::class, 'allowed');
        $allowedFields->setAccessible(true);
        $allowedFields->setValue($abstractProcessor, ['allowed_field_1', 'allowed_field_2']);

        $getImportantFields = new ReflectionMethod(AbstractProcessor::class, 'getImportantFields');
        $getImportantFields->setAccessible(true);

        $expectedImportantFields = ['type', 'key_1', 'key_2', 'allowed_field_1', 'allowed_field_2'];
        $this->assertSame($expectedImportantFields, $getImportantFields->invoke($abstractProcessor));
    }

    protected function getMockAbstractProcessor(): MockObject
    {
        $container = $this->createMock(ContainerInterface::class);
        $abstractProcessor = $this->getMockForAbstractClass(AbstractProcessor::class);
        $abstractProcessor->injectContainer($container);
        $abstractProcessor->method('buildResolver')->willReturn($this->createMock(StaticJoinResolver::class));
        return $abstractProcessor;
    }
}
