<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\PreProcessing;

use Closure;
use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\TextProcessor;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\TextResolver;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\TextProcessor
 */
class TextProcessorTest extends UnitTestCase
{
    /**
     * @covers ::process
     * @covers ::additionalPreProcess
     * @covers ::getImportantFields
     * @covers ::buildResolver
     */
    public function testTextProcessorReturnsResolverForTextColumnWithEnableRichtext(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor();
        $textProcessor->injectContainer($container);
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => true,
        ]);
        $this->assertTrue($processingResult->isCompatible());
        $value = $processingResult->getValue();
        $this->assertSame($value['tca'], [
            'type' => 'text',
            'enableRichtext' => true,
        ]);
        $this->assertInstanceOf(Resolver::class, $value['resolver']);
    }

    /**
     * @depends testTextProcessorReturnsResolverForTextColumnWithEnableRichtext
     * @covers ::buildResolver
     * @covers ::findRelationsInText
     */
    public function testTextProcessorClosureResolvesDemandForTypo3PageUrns(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor();
        $textProcessor->injectContainer($container);
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => true,
        ]);

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $databaseRecord->method('getClassification')->willReturn('tableNameFoo');
        $databaseRecord->method('getId')->willReturn(1);
        $databaseRecord->method('getLocalProps')->willReturn(['fieldNameBar' => 'lalala \'t3://page?uid=14\' fofofo']);
        $databaseRecord->method('getForeignProps')->willReturn([]);

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];
        $demands = new Demands();
        $resolver->resolve($demands, $databaseRecord);

        $this->assertInstanceOf(TextResolver::class, $resolver);
    }

    /**
     * @depends testTextProcessorReturnsResolverForTextColumnWithEnableRichtext
     * @covers ::buildResolver
     * @covers ::findRelationsInText
     */
    public function testTextProcessorClosureResolvesDemandForTypo3FileUrns(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor();
        $textProcessor->injectContainer($container);
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => true,
        ]);

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $databaseRecord->method('getClassification')->willReturn('tableNameFoo');
        $databaseRecord->method('getId')->willReturn(1);
        $databaseRecord->method('getLocalProps')->willReturn(['fieldNameBar' => 'lalala t3://file?uid=14 fofofo']);
        $databaseRecord->method('getForeignProps')->willReturn([]);

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];

        $this->assertInstanceOf(TextResolver::class, $resolver);
    }

    /**
     * @depends testTextProcessorReturnsResolverForTextColumnWithEnableRichtext
     * @covers ::buildResolver
     * @covers ::findRelationsInText
     */
    public function testTextProcessorClosureResolvesEmptyDemandWhenNoTextContainsNoValidUrl(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor();
        $textProcessor->injectContainer($container);
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => true,
        ]);

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $databaseRecord->method('getClassification')->willReturn('tableNameFoo');
        $databaseRecord->method('getId')->willReturn(1);
        $databaseRecord->method('getLocalProps')->willReturn(['fieldNameBar' => 'lalalat3://file?uid=14 fofofo']);
        $databaseRecord->method('getForeignProps')->willReturn([]);

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];

        $this->assertInstanceOf(TextResolver::class, $resolver);
    }

    /**
     * @depends testTextProcessorReturnsResolverForTextColumnWithEnableRichtext
     * @covers ::buildResolver
     * @covers ::findRelationsInText
     */
    public function testTextProcessorClosureResolvesDemandForDifferentLocalAndForeignValues(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor();
        $textProcessor->injectContainer($container);
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => true,
        ]);

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $databaseRecord->method('getClassification')->willReturn('tableNameFoo');
        $databaseRecord->method('getId')->willReturn(1);
        $databaseRecord->method('getLocalProps')->willReturn(['fieldNameBar' => 'lalala t3://page?uid=14 fofofo']);
        $databaseRecord->method('getForeignProps')->willReturn(['fieldNameBar' => 'lalala t3://page?uid=15 fofofo']);

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];

        $this->assertInstanceOf(TextResolver::class, $resolver);
    }

    /**
     * @covers ::process
     */
    public function testTextProcessorReturnsIncompatibleResultWhenRichtextFieldIsMissing(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor();
        $textProcessor->injectContainer($container);
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
        ]);

        $this->assertFalse($processingResult->isCompatible());
    }

    /**
     * @covers ::process
     */
    public function testTextProcessorReturnsIncompatibleResultWhenRichtextFieldIsFalse(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor();
        $textProcessor->injectContainer($container);
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => false,
        ]);

        $this->assertFalse($processingResult->isCompatible());
    }
}
