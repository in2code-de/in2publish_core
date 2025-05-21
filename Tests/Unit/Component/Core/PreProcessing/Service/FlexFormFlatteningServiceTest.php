<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\Service;

use In2code\In2publishCore\Component\Core\PreProcessing\Service\FlexFormFlatteningService;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(FlexFormFlatteningService::class, 'flattenFlexFormDefinition')]
#[CoversMethod(FlexFormFlatteningService::class, 'flattenFieldFlexForm')]
class FlexFormFlatteningServiceTest extends UnitTestCase
{
    public function testFlattenFieldFlexFormDefinitionFlattensDefaultFlexForm(): void
    {
        $flexFormDefinition = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'TCEforms' => [
                            'sheetTitle' => 'Common',
                        ],
                        'type' => 'array',
                        'el' => [
                            'settings.pid' => [
                                'TCEforms' => [
                                    'exclude' => '1',
                                    'label' => 'test',
                                    'config' => [
                                        'type' => 'group',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $flexFormFieldAfter = [
            'settings.pid' => [
                'exclude' => '1',
                'label' => 'test',
                'config' => [
                    'type' => 'group',
                ],
            ],
        ];

        $flexFormFlatteningService = new FlexFormFlatteningService();
        $result = $flexFormFlatteningService->flattenFlexFormDefinition($flexFormDefinition);
        $this->assertEquals($flexFormFieldAfter, $result);
    }
}
