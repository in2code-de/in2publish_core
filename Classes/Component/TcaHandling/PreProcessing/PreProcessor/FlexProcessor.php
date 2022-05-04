<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\FlexFormFlatteningService;
use In2code\In2publishCore\Component\TcaHandling\Resolver\FlexResolver;
use In2code\In2publishCore\Component\TcaHandling\Resolver\Resolver;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Service\FlexFormService;

use function array_key_exists;
use function array_keys;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

class FlexProcessor extends AbstractProcessor
{
    protected FlexFormTools $flexFormTools;
    protected FlexFormFlatteningService $flexFormFlatteningService;
    protected FlexResolver $resolver;
    protected string $type = 'flex';
    protected array $forbidden = [
        'ds_pointerField_searchParent' => 'ds_pointerField_searchParent is not supported',
        'ds_pointerField_searchParent_subField' => 'ds_pointerField_searchParent_subField is not supported',
    ];
    protected array $required = [
        'ds' => 'can not resolve flexform values without "ds"',
    ];
    protected array $allowed = [
        'search',
        'ds_pointerField',
    ];

    public function injectFlexFormTools(FlexFormTools $flexFormTools): void
    {
        $this->flexFormTools = $flexFormTools;
    }

    public function injectFlexFormFlatteningService(FlexFormFlatteningService $flexFormFlatteningService): void
    {
        $this->flexFormFlatteningService = $flexFormFlatteningService;
    }

    public function injectResolver(FlexResolver $resolver): void
    {
        $this->resolver = $resolver;
    }

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        if (
            !array_key_exists('ds_pointerField', $tca)
            && empty($tca['ds']['default'])
        ) {
            return ['Can not resolve flexform values without "ds_pointerField" or default value'];
        }
        return [];
    }

    protected function buildResolver(string $table, string $column, array $processedTca): Resolver
    {
        foreach (array_keys($processedTca['ds']) as $dsPointerValue) {
            $dataStructureIdentifier = json_encode(
                [
                    'type' => 'tca',
                    'tableName' => $table,
                    'fieldName' => $column,
                    'dataStructureKey' => $dsPointerValue,
                ],
                JSON_THROW_ON_ERROR
            );

            $parsedFlexForm = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            $flattenedFlexForm = $this->flexFormFlatteningService->flattenFlexFormDefinition($parsedFlexForm);

            $this->tcaPreProcessingService->preProcessTcaColumns(
                $table . '/' . $column . '/' . $dsPointerValue,
                $flattenedFlexForm
            );
        }

        $resolver = clone $this->resolver;
        $resolver->configure($table, $column, $processedTca);
        return $resolver;
    }
}
