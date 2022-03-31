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

use Closure;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service\FlexFormFlatteningService;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_replace_recursive;
use function array_unique;
use function implode;
use function is_array;
use function json_decode;
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
    protected $type = 'flex';

    /** @var FlexFormTools */
    protected $flexFormTools;

    /** @var FlexFormService */
    protected $flexFormService;

    /** @var FlexFormFlatteningService */
    protected $flexFormFlatteningService;

    public function injectFlexFormTools(FlexFormTools $flexFormTools): void
    {
        $this->flexFormTools = $flexFormTools;
    }

    public function injectFlexFormService(FlexFormService $flexFormService): void
    {
        $this->flexFormService = $flexFormService;
    }

    public function injectFlexFormFlatteningService(FlexFormFlatteningService $flexFormFlatteningService): void
    {
        $this->flexFormFlatteningService = $flexFormFlatteningService;
    }

    protected $forbidden = [
        'ds_pointerField_searchParent' => 'ds_pointerField_searchParent is not supported',
        'ds_pointerField_searchParent_subField' => 'ds_pointerField_searchParent_subField is not supported',
    ];

    protected $required = [
        'ds' => 'can not resolve flexform values without "ds"',
    ];

    protected $allowed = [
        'search',
        'ds_pointerField',
    ];

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        if (
            !array_key_exists('ds_pointerField', $tca)
            && empty($tca['ds']['default'])
        ) {
            return ['can not resolve flexform values without "ds_pointerField" or default value'];
        }
        return [];
    }

    protected function buildResolver(string $table, string $column, array $processedTca): Closure
    {
        $tcaPreProcessingService = GeneralUtility::makeInstance(TcaPreProcessingService::class);

        foreach ($processedTca['ds'] as $dsPointerValue => $flexForm) {
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

            $tcaPreProcessingService->preProcessTcaColumns(
                $table . '/' . $column . '/' . $dsPointerValue,
                $flattenedFlexForm
            );
        }

        return function (DatabaseRecord $record) use (
            $table,
            $column,
            $processedTca,
            $tcaPreProcessingService
        ): array {
            $dataStructureIdentifierJson = $this->flexFormTools->getDataStructureIdentifier(
                ['config' => $processedTca],
                $table,
                $column,
                $record->getLocalProps() ?: $record->getForeignProps()
            );
            $dataStructureKey = json_decode($dataStructureIdentifierJson, true, 512, JSON_THROW_ON_ERROR)['dataStructureKey'];

            $localValues = $record->getLocalProps()[$column] ?? [];
            if ([] !== $localValues) {
                $localValues = $this->flexFormService->convertFlexFormContentToArray($localValues);
            }
            $localValues = $this->flattenFlexFormData($localValues);
            $foreignValues = $record->getForeignProps()[$column] ?? [];
            if ([] !== $foreignValues) {
                $foreignValues = $this->flexFormService->convertFlexFormContentToArray($foreignValues);
            }
            $localValues = $this->flattenFlexFormData($localValues);

            $flexFormFields = array_unique(array_merge(array_keys($localValues), array_keys($foreignValues)));

            $flexFormTableName = $table . '/' . $column . '/' . $dataStructureKey;
            $virtualRecord = new DatabaseRecord($flexFormTableName, $record->getId(), $localValues, $foreignValues);

            $compatibleTcaParts = $tcaPreProcessingService->getCompatibleTcaParts();

            $demands = [];
            foreach ($flexFormFields as $flexFormField) {
                $resolver = $compatibleTcaParts[$flexFormTableName][$flexFormField]['resolver'] ?? null;
                if (null !== $resolver) {
                    $demands[] = $resolver($virtualRecord);
                }
            }
            return array_replace_recursive([], ...$demands);
        };
    }

    protected function flattenFlexFormData(array $data, array $path = []): array
    {
        $newData = [];
        foreach ($data as $key => $value) {
            $path[] = $key;
            if (is_array($value)) {
                foreach ($this->flattenFlexFormData($value, $path) as $subKey => $subVal) {
                    $newData[$subKey] = $subVal;
                }
            } else {
                $newData[implode('.', $path)] = $value;
            }
            array_pop($path);
        }
        return $newData;
    }
}
