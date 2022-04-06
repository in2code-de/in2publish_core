<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\PreProcessing;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\FlexProcessor;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\FlexFormFlatteningService;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Service\FlexFormService;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\FlexProcessor
 */
class FlexProcessorTest extends UnitTestCase
{
    public function testFlexProcessorRejectsTcaWithoutDefaultValueOrDsPointerField(): void
    {
        $flexProcessor = new FlexProcessor();
        $processingResult = $flexProcessor->process('tableNameFoo', 'fieldNameBar', [
            'type' => 'flex'
        ]);
        $this->assertFalse($processingResult->isCompatible());
    }

    public function testTcaWithTwoSheetsIsResolved(): void
    {
        $flexProcessor = new FlexProcessor();

        // mock dependencies
        $flexFormTools = $this->createMock(FlexFormTools::class);
        $flexFormService = $this->createMock(FlexFormService::class);
        $flexFormFlatteningService = $this->createMock(FlexFormFlatteningService::class);

        $flexProcessor->injectFlexFormTools($flexFormTools);
        $flexProcessor->injectFlexFormService($flexFormService);
        $flexProcessor->injectFlexFormFlatteningService($flexFormFlatteningService);

        $flexFieldTca =
            [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sSheetdescription_1>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet description 1</sheetTitle>
                                            <sheetDescription>
                                                sheetDescription 1
                                            </sheetDescription>
                                            <sheetShortDescr>
                                                sheetShortDescr
                                            </sheetShortDescr>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <input_1>
                                                <TCEforms>
                                                    <label>input_1</label>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_1>
                                        </el>
                                    </ROOT>
                                </sSheetdescription_1>
                                <sSheetdescription_2>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet description 2</sheetTitle>
                                            <sheetDescription>
                                                foo
                                            </sheetDescription>
                                            <sheetShortDescr>
                                                bar
                                           </sheetShortDescr>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <input_2>
                                                <TCEforms>
                                                    <label>input_2</label>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_2>
                                        </el>
                                    </ROOT>
                                </sSheetdescription_2>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ];

        $processingResult = $flexProcessor->process('tableNameFoo', 'fieldNameBar', $flexFieldTca);
        $this->assertTrue($processingResult->isCompatible());
    }
}
