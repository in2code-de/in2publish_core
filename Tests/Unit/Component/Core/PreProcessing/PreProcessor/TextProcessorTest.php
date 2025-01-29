<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\TextProcessor;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Component\Core\Resolver\TextResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\TextProcessor
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

        $textProcessor = new TextProcessor($container);
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
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testTextProcessorClosureResolvesDemandForTypo3PageUrns(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor($container);
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
        $demands = new DemandsCollection();
        $resolver->resolve($demands, $databaseRecord);

        $this->assertInstanceOf(TextResolver::class, $resolver);
    }

    /**
     * @depends testTextProcessorReturnsResolverForTextColumnWithEnableRichtext
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testTextProcessorClosureResolvesDemandForTypo3FileUrns(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor($container);
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
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testTextProcessorClosureResolvesEmptyDemandWhenNoTextContainsNoValidUrl(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor($container);
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
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testTextProcessorClosureResolvesDemandForDifferentLocalAndForeignValues(): void
    {
        $resolver = $this->createMock(TextResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($resolver);

        $textProcessor = new TextProcessor($container);
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

        $textProcessor = new TextProcessor($container);
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

        $textProcessor = new TextProcessor($container);
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => false,
        ]);

        $this->assertFalse($processingResult->isCompatible());
    }
}
