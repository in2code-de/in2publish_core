<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\AbstractProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\Exception\MissingPreProcessorTypeException;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionProperty;

#[CoversMethod(AbstractProcessor::class, 'process')]
#[CoversMethod(AbstractProcessor::class, 'buildResolver')]
#[CoversMethod(AbstractProcessor::class, 'getImportantFields')]
class AbstractProcessorTest extends UnitTestCase
{
    public function testProcessingResultIsImcompatibleIfThereAreForbiddenKeysInTca(): void
    {
        $abstractProcessor = $this->getMockAbstractProcessorWithJoinResolver();

        $forbidden = new ReflectionProperty(AbstractProcessor::class, 'forbidden');
        $forbidden->setAccessible(true);
        $forbidden->setValue(
            $abstractProcessor,
            ['forbidden_key_1' => 'first_reason', 'forbidden_key_2' => 'second_reason'],
        );

        $tca = ['type' => 'inline', 'forbidden_key_1' => 'foo'];

        $processingResult = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue();
        $this->assertSame('first_reason', $reason);

        $tca = ['type' => 'inline', 'forbidden_key_2' => 'foo'];
        $processingResult = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $reason = $processingResult->getValue();
        $this->assertFalse($processingResult->isCompatible());
        $this->assertSame('second_reason', $reason);
    }

    public function testProcessingResultIsIncompatibleIfRequiredFieldIsMissing(): void
    {
        $abstractProcessor = $this->getMockAbstractProcessorWithJoinResolver();

        $required = new ReflectionProperty(AbstractProcessor::class, 'required');
        $required->setAccessible(true);
        $required->setValue($abstractProcessor, ['key_1' => 'Key 1 is required', 'key_2' => 'Key 2 is required']);

        $tca = ['type' => 'inline'];

        $processingResult = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());

        $tca = ['type' => 'inline', 'key_1' => 'foo'];
        $processingResult = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue();
        $this->assertSame('Key 2 is required', $reason);

        $tca = ['type' => 'inline', 'key_2' => 'foo'];
        $processingResult = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $this->assertFalse($processingResult->isCompatible());
        $reason = $processingResult->getValue();
        $this->assertSame('Key 1 is required', $reason);
    }

    public function testExceptionIsThrownIfTypeIsMissing(): void
    {
        $abstractProcessor = $this->getMockAbstractProcessorWithJoinResolver();
        // type is not set
        $this->expectException(MissingPreProcessorTypeException::class);
        $abstractProcessor->getType();

        // type is set to 'processor_type'
        $abstractProcessor->method('getType')->willReturn('processor_type');
        $this->assertSame('processor_type', $abstractProcessor->getType());
    }

    public function testProcessingResultIsIncompatibleIfNoResolverIsFound(): void
    {
        $abstractProcessor = $this->getMockAbstractProcessor();
        $abstractProcessor->method('buildResolver')->willReturn(null);

        $tca = ['type' => 'inline'];
        $result = $abstractProcessor->process('tableNameFoo', 'fieldNameBar', $tca);
        $reason = $result->getValue();
        $this->assertFalse($result->isCompatible());
        $this->assertSame(
            'The processor did not return a valid resolver. The target table might be excluded or empty.',
            $reason,
        );
    }

    public function testMethodGetImportantFields(): void
    {
        $abstractProcessor = $this->getMockAbstractProcessor();

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

    protected function getMockAbstractProcessorWithJoinResolver(): MockObject
    {
        $container = $this->createMock(ContainerInterface::class);

        $abstractProcessor = $this->getMockBuilder(AbstractProcessor::class)
                                  ->onlyMethods(['buildResolver'])
                                  ->setConstructorArgs([$container])
                                  ->getMock();

        $abstractProcessor->method('buildResolver')
                          ->willReturn($this->createMock(StaticJoinResolver::class));

        return $abstractProcessor;
    }

    protected function getMockAbstractProcessor(): MockObject
    {
        $container = $this->createMock(ContainerInterface::class);

        $abstractProcessor = $this->getMockBuilder(AbstractProcessor::class)
                                  ->onlyMethods(['buildResolver'])
                                  ->setConstructorArgs([$container])
                                  ->getMock();

        return $abstractProcessor;
    }
}
