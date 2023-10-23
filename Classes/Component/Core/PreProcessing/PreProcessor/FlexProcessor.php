<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\CommonInjection\FlexFormToolsInjection;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\FlexFormFlatteningServiceInjection;
use In2code\In2publishCore\Component\Core\PreProcessing\TcaPreProcessingServiceInjection;
use In2code\In2publishCore\Component\Core\Resolver\FlexResolver;
use In2code\In2publishCore\Component\Core\Resolver\Resolver;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;

use function array_key_exists;
use function array_keys;
use function json_encode;

use const JSON_THROW_ON_ERROR;

class FlexProcessor extends AbstractProcessor
{
    use FlexFormToolsInjection;
    use FlexFormFlatteningServiceInjection;
    use TcaPreProcessingServiceInjection;

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
                JSON_THROW_ON_ERROR,
            );

            try {
                $parsedFlexForm = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            } catch (InvalidIdentifierException $e) {
                // Skip invalid flex form configs
            }
            $flattenedFlexForm = $this->flexFormFlatteningService->flattenFlexFormDefinition($parsedFlexForm);

            $this->tcaPreProcessingService->preProcessTcaColumns(
                $table . '/' . $column . '/' . $dsPointerValue,
                $flattenedFlexForm,
            );
        }

        $resolver = $this->container->get(FlexResolver::class);
        $resolver->configure($table, $column, $processedTca);
        return $resolver;
    }
}
