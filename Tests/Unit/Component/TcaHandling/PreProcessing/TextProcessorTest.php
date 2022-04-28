<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\PreProcessing;

use Closure;
use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\TextProcessor;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Tests\UnitTestCase;

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
        $textProcessor = new TextProcessor();
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
        $textProcessor = new TextProcessor();
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

        $expectedDemand = [];
        $expectedDemand['pages']['']['uid'][14]['tableNameFoo' . "\0" . 1] = $databaseRecord;

        $this->assertSame($expectedDemand, $demands->getSelect());
    }

    /**
     * @depends testTextProcessorReturnsResolverForTextColumnWithEnableRichtext
     * @covers ::buildResolver
     * @covers ::findRelationsInText
     */
    public function testTextProcessorClosureResolvesDemandForTypo3FileUrns(): void
    {
        $textProcessor = new TextProcessor();
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => true,
        ]);

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $databaseRecord->method('getClassification')->willReturn('tableNameFoo');
        $databaseRecord->method('getId')->willReturn(1);
        $databaseRecord->method('getLocalProps')->willReturn(['fieldNameBar' => 'lalala t3://file?uid=14 fofofo']);
        $databaseRecord->method('getForeignProps')->willReturn([]);

        $demands = new Demands();

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];
        $resolver->resolve($demands, $databaseRecord);

        $expectedDemand = [];
        $expectedDemand['sys_file']['']['uid'][14]['tableNameFoo' . "\0" . 1] = $databaseRecord;

        $this->assertSame($expectedDemand, $demands->getSelect());
    }

    /**
     * @depends testTextProcessorReturnsResolverForTextColumnWithEnableRichtext
     * @covers ::buildResolver
     * @covers ::findRelationsInText
     */
    public function testTextProcessorClosureResolvesEmptyDemandWhenNoTextContainsNoValidUrl(): void
    {
        $textProcessor = new TextProcessor();
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => true,
        ]);

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $databaseRecord->method('getClassification')->willReturn('tableNameFoo');
        $databaseRecord->method('getId')->willReturn(1);
        $databaseRecord->method('getLocalProps')->willReturn(['fieldNameBar' => 'lalalat3://file?uid=14 fofofo']);
        $databaseRecord->method('getForeignProps')->willReturn([]);

        $demands = new Demands();

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];
        $resolver->resolve($demands, $databaseRecord);

        $expectedDemand = [];

        $this->assertSame($expectedDemand, $demands->getSelect());
    }

    /**
     * @depends testTextProcessorReturnsResolverForTextColumnWithEnableRichtext
     * @covers ::buildResolver
     * @covers ::findRelationsInText
     */
    public function testTextProcessorClosureResolvesDemandForDifferentLocalAndForeignValues(): void
    {
        $textProcessor = new TextProcessor();
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => true,
        ]);

        $databaseRecord = $this->createMock(DatabaseRecord::class);
        $databaseRecord->method('getClassification')->willReturn('tableNameFoo');
        $databaseRecord->method('getId')->willReturn(1);
        $databaseRecord->method('getLocalProps')->willReturn(['fieldNameBar' => 'lalala t3://page?uid=14 fofofo']);
        $databaseRecord->method('getForeignProps')->willReturn(['fieldNameBar' => 'lalala t3://page?uid=15 fofofo']);

        $demands = new Demands();

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];
        $resolver->resolve($demands, $databaseRecord);

        $expectedDemand = [];
        $expectedDemand['pages']['']['uid'][14]['tableNameFoo' . "\0" . 1] = $databaseRecord;
        $expectedDemand['pages']['']['uid'][15]['tableNameFoo' . "\0" . 1] = $databaseRecord;

        $this->assertSame($expectedDemand, $demands->getSelect());
    }

    /**
     * @covers ::process
     */
    public function testTextProcessorReturnsIncompatibleResultWhenRichtextFieldIsMissing(): void
    {
        $textProcessor = new TextProcessor();
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
        $textProcessor = new TextProcessor();
        $processingResult = $textProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'text',
            'enableRichtext' => false,
        ]);

        $this->assertFalse($processingResult->isCompatible());
    }
}
