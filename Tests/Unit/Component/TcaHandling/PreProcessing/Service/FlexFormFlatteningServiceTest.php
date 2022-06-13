<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\PreProcessing\Service;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\FlexFormFlatteningService;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\FlexFormFlatteningService
 */
class FlexFormFlatteningServiceTest extends UnitTestCase
{
    /**
     * @covers ::flattenFlexFormDefinition
     * @covers ::flattenFieldFlexForm
     */
    public function testFlattenFieldFlexFormDefinitionFlattensDefaultFlexForm(): void
    {
        $flexFormDefinition = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'TCEforms' => [
                            'sheetTitle' => 'Common'
                        ],
                        'type' => 'array',
                        'el' => [
                            'settings.pid' => [
                                'TCEforms' => [
                                    'exclude' => '1',
                                    'label' => 'test',
                                    'config' => [
                                        'type' => 'group'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $flexFormFieldAfter = [
            'settings.pid' => [
                'exclude' => '1',
                'label' => 'test',
                'config' => [
                    'type' => 'group'
                ]
            ]
        ];

        $flexFormFlatteningService = new FlexFormFlatteningService();
        $result = $flexFormFlatteningService->flattenFlexFormDefinition($flexFormDefinition);
        $this->assertEquals($flexFormFieldAfter, $result);
    }

}
