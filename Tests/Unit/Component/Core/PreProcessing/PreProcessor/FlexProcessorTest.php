<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\FlexProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\FlexFormFlatteningService;
use In2code\In2publishCore\Component\Core\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\Resolver\FlexResolver;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Service\FlexFormService;

use function json_encode;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\FlexProcessor
 */
class FlexProcessorTest extends UnitTestCase
{
    /**
     * @covers ::additionalPreProcess
     */
    public function testFlexProcessorRejectsTcaWithoutDefaultValueOrDsPointerField(): void
    {
        $flexProcessor = new FlexProcessor();
        $processingResult = $flexProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'flex',
        ]);
        $this->assertFalse($processingResult->isCompatible());
    }

    /**
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testTcaWithTwoSheetsIsResolved(): void
    {
        $flexProcessor = new FlexProcessor();

        // mock dependencies
        $flexResolver = $this->createMock(FlexResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($flexResolver);
        $flexFormTools = $this->createMock(FlexFormTools::class);
        $flexFormFlatteningService = $this->createMock(FlexFormFlatteningService::class);
        $tcaPreProcessingService = $this->createMock(TcaPreProcessingService::class);

        $flexProcessor->injectContainer($container);
        $flexProcessor->injectFlexFormTools($flexFormTools);
        $flexProcessor->injectFlexFormFlatteningService($flexFormFlatteningService);
        $flexProcessor->injectTcaPreProcessingService($tcaPreProcessingService);

        $flexFieldTca = [
            'type' => 'flex',
            'ds' => [
                'default' => 'unused',
            ],
        ];

        $processingResult = $flexProcessor->process('tableNameFoo', 'fieldNameBar', $flexFieldTca);
        $this->assertTrue($processingResult->isCompatible());
    }

    /**
     * @noinspection JsonEncodingApiUsageInspection
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testFlexProcessorDelegatesFlexFormResolvingToTcaPreProcessingService(): void
    {
        $json1 = json_encode([
            'type' => 'tca',
            'tableName' => 'tableNameFoo',
            'fieldName' => 'fieldNameBar',
            'dataStructureKey' => 'foo_pi1,bar',
        ]);
        $json2 = json_encode([
            'type' => 'tca',
            'tableName' => 'tableNameFoo',
            'fieldName' => 'fieldNameBar',
            'dataStructureKey' => 'foo_pi2,baz',
        ]);

        $flexProcessor = new FlexProcessor();

        $flexResolver = $this->createMock(FlexResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($flexResolver);
        $flexFormTools = $this->createMock(FlexFormTools::class);

        $flexFormTools->expects($this->exactly(2))->method('parseDataStructureByIdentifier');
        $flexFormFlatteningService = $this->createMock(FlexFormFlatteningService::class);
        $flexFormFlatteningService->expects($this->exactly(2))
                                  ->method('flattenFlexFormDefinition')
                                  ->willReturnOnConsecutiveCalls(
                                      ['foo' => 'bar'],
                                      ['bar' => 'baz'],
                                  );
        $tcaPreProcessingService = $this->createMock(TcaPreProcessingService::class);
        $tcaPreProcessingService->expects($this->exactly(2))->method('preProcessTcaColumns');

        $flexProcessor->injectContainer($container);
        $flexProcessor->injectFlexFormTools($flexFormTools);
        $flexProcessor->injectFlexFormFlatteningService($flexFormFlatteningService);
        $flexProcessor->injectTcaPreProcessingService($tcaPreProcessingService);

        $flexFieldTca = [
            'type' => 'flex',
            'ds_pointerField' => 'unused',
            'ds' => [
                'foo_pi1,bar' => 'unused',
                'foo_pi2,baz' => 'unused',
            ],
        ];

        $flexProcessor->process('tableNameFoo', 'fieldNameBar', $flexFieldTca);
    }

    /**
     * @noinspection JsonEncodingApiUsageInspection
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testFlexProcessorResolvesDemandForFlexFormFields(): void
    {
        $databaseRecord = new DatabaseRecord('tableNameFoo', 1, ['fieldNameBar' => 1], [], []);

        $flexProcessor = new FlexProcessor();

        $flexFormTools = $this->createMock(FlexFormTools::class);
        $flexFormTools->method('getDataStructureIdentifier')->willReturn(
            json_encode([
                'type' => 'tca',
                'tableName' => 'tableNameFoo',
                'fieldName' => 'fieldNameBar',
                'dataStructureKey' => 'foo_pi2,baz',
            ]),
        );

        $flexFormContent = [
            'select' => [
                'fooBar' => 5,
            ],
            'inline' => [
                'barFoo' => 3,
            ],
        ];

        $called = ['select.fooBar' => 0, 'inline.barFoo' => 0];

        $compatibleTcaParts = [];
        $compatibleTcaParts['tableNameFoo/fieldNameBar/foo_pi2,baz']['select.fooBar']['resolver'] =
            new class ($called, $databaseRecord) implements Resolver {
                protected array $called;
                protected DatabaseRecord $databaseRecord;

                public function __construct(array &$called, DatabaseRecord $databaseRecord)
                {
                    $this->called = &$called;
                    $this->databaseRecord = $databaseRecord;
                }

                public function getTargetTables(): array
                {
                    return [];
                }

                public function resolve(Demands $demands, Record $record): void
                {
                    $this->called['select.fooBar']++;
                    $demands->addDemand(
                        new SelectDemand('tableNameBar', '', 'columnNameFoo', 3, $this->databaseRecord),
                    );
                }
            };
        $compatibleTcaParts['tableNameFoo/fieldNameBar/foo_pi2,baz']['inline.barFoo']['resolver'] =
            new class ($called, $databaseRecord) implements Resolver {
                protected array $called;
                protected DatabaseRecord $databaseRecord;

                public function __construct(array &$called, DatabaseRecord $databaseRecord)
                {
                    $this->called = &$called;
                    $this->databaseRecord = $databaseRecord;
                }

                public function getTargetTables(): array
                {
                    return [];
                }

                public function resolve(Demands $demands, Record $record): void
                {
                    $this->called['inline.barFoo']++;
                    $demands->addDemand(
                        new SelectDemand('tableNameFoo', '', 'columnNameBar', 5, $this->databaseRecord),
                    );
                }
            };

        $flexResolver = $this->createMock(FlexResolver::class);
        $container = $this->createMock(Container::class);
        $container->method('get')->willReturn($flexResolver);
        $flexFormService = $this->createMock(FlexFormService::class);
        $flexFormService->method('convertFlexFormContentToArray')->willReturn($flexFormContent);
        $flexFormFlatteningService = $this->createMock(FlexFormFlatteningService::class);
        $tcaPreProcessingService = $this->createMock(TcaPreProcessingService::class);
        $tcaPreProcessingService->method('getCompatibleTcaParts')->willReturn($compatibleTcaParts);

        $flexProcessor->injectContainer($container);
        $flexProcessor->injectFlexFormTools($flexFormTools);
        $flexProcessor->injectFlexFormFlatteningService($flexFormFlatteningService);
        $flexProcessor->injectTcaPreProcessingService($tcaPreProcessingService);

        $flexFieldTca = [
            'type' => 'flex',
            'ds_pointerField' => 'unused',
            'ds' => [
                'foo_pi2,baz' => 'unused',
            ],
        ];

        $processingResult = $flexProcessor->process('tableNameFoo', 'fieldNameBar', $flexFieldTca);

        /** @var Resolver $resolver */
        $resolver = $processingResult->getValue()['resolver'];

        $this->assertInstanceOf(FlexResolver::class, $resolver);
    }
}
