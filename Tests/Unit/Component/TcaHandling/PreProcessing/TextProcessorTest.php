<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\PreProcessing;

use Closure;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\TextProcessor;
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
        $this->assertInstanceOf(Closure::class, $value['resolver']);
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

        $resolver = $processingResult->getValue()['resolver'];
        $demand = $resolver($databaseRecord);

        $expectedDemand = [];
        $expectedDemand['select']['pages']['']['uid'][14] = $databaseRecord;

        $this->assertSame($expectedDemand, $demand);
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

        $resolver = $processingResult->getValue()['resolver'];
        $demand = $resolver($databaseRecord);

        $expectedDemand = [];
        $expectedDemand['select']['sys_file']['']['uid'][14] = $databaseRecord;

        $this->assertSame($expectedDemand, $demand);
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

        $resolver = $processingResult->getValue()['resolver'];
        $demand = $resolver($databaseRecord);

        $expectedDemand = [];

        $this->assertSame($expectedDemand, $demand);
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

        $resolver = $processingResult->getValue()['resolver'];
        $demand = $resolver($databaseRecord);

        $expectedDemand = [];
        $expectedDemand['select']['pages']['']['uid'][14] = $databaseRecord;
        $expectedDemand['select']['pages']['']['uid'][15] = $databaseRecord;

        $this->assertSame($expectedDemand, $demand);
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
