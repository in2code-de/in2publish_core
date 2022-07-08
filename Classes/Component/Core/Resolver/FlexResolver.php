<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\FlexFormFlatteningService;
use In2code\In2publishCore\Component\Core\Service\ResolverService;
use In2code\In2publishCore\Domain\Model\DatabaseEntityRecord;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\VirtualFlexFormRecord;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Service\FlexFormService;

use function array_keys;
use function array_merge;
use function array_pop;
use function array_unique;
use function implode;
use function is_array;
use function json_decode;
use function preg_match;
use function preg_quote;
use function str_contains;
use function str_replace;

use const JSON_THROW_ON_ERROR;

class FlexResolver extends AbstractResolver
{
    protected FlexFormTools $flexFormTools;
    protected FlexFormService $flexFormService;
    protected FlexFormFlatteningService $flexFormFlatteningService;
    protected ResolverService $resolverService;
    protected string $table;
    protected string $column;
    protected array $processedTca;

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

    public function injectResolverService(ResolverService $resolverService): void
    {
        $this->resolverService = $resolverService;
    }

    public function configure(string $table, string $column, array $processedTca): void
    {
        $this->table = $table;
        $this->column = $column;
        $this->processedTca = $processedTca;
    }

    public function getTargetTables(): array
    {
        return array_keys($GLOBALS['TCA']);
    }

    public function resolve(Demands $demands, Record $record): void
    {
        if (!($record instanceof DatabaseEntityRecord)) {
            return;
        }
        $dataStructureIdentifierJson = $this->flexFormTools->getDataStructureIdentifier(
            ['config' => $this->processedTca],
            $this->table,
            $this->column,
            $record->getLocalProps() ?: $record->getForeignProps()
        );
        $dataStructureKey = json_decode(
            $dataStructureIdentifierJson,
            true,
            512,
            JSON_THROW_ON_ERROR
        )['dataStructureKey'];

        $localValues = $record->getLocalProps()[$this->column] ?? [];
        $localValues = $this->convertAndFlattenFlexFormData($localValues, $record);

        $foreignValues = $record->getForeignProps()[$this->column] ?? [];
        $foreignValues = $this->convertAndFlattenFlexFormData($foreignValues, $record);

        if (empty($localValues) && empty($foreignValues)) {
            return;
        }

        $flexFormFields = array_unique(array_merge(array_keys($localValues), array_keys($foreignValues)));

        $flexFormTableName = $this->table . '/' . $this->column . '/' . $dataStructureKey;
        $virtualRecord = new VirtualFlexFormRecord($record, $flexFormTableName, $localValues, $foreignValues);

        $resolvers = $this->resolverService->getResolversForTable($flexFormTableName);

        $expressions = [];
        foreach ($resolvers as $field => $resolver) {
            if (str_contains($field, '[ANY]')) {
                $regEx = '/' . str_replace('\[ANY\]', '[\w\d]+', preg_quote($field, '/')) . '/';
                $expressions[$regEx] = $resolver;
            }
        }

        foreach ($flexFormFields as $flexFormField) {
            if (isset($resolvers[$flexFormField])) {
                $resolver = $resolvers[$flexFormField];
                $resolver->resolve($demands, $virtualRecord);
            } else {
                foreach ($expressions as $regEx => $resolver) {
                    if (1 === preg_match($regEx, $flexFormField)) {
                        $resolver->resolve($demands, $virtualRecord);
                    }
                }
            }
        }
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

    protected function convertAndFlattenFlexFormData($data, $record): array
    {
        if ([] !== $data) {
            $data = $this->flexFormService->convertFlexFormContentToArray($data);
            $data['pid'] = $record->getProp('pid');
            $data = $this->flattenFlexFormData($data);
        }
        return $data;
    }
}
