<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demand\Demands;
use In2code\In2publishCore\Domain\Model\Record;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_filter;
use function array_merge;
use function array_unique;
use function in_array;
use function strrpos;
use function substr;

class GroupMmMultiTableResolver extends AbstractResolver
{
    protected string $mmTable;
    protected string $column;
    protected string $selectField;
    protected string $additionalWhere;
    protected array $tables;

    public function configure(
        array $tables,
        string $mmTable,
        string $column,
        string $selectField,
        string $additionalWhere
    ): void {
        $this->tables = $tables;
        $this->mmTable = $mmTable;
        $this->column = $column;
        $this->selectField = $selectField;
        $this->additionalWhere = $additionalWhere;
    }

    public function getTargetTables(): array
    {
        return $this->tables;
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $localValue = $record->getLocalProps()[$this->column] ?? '';
        $foreignValue = $record->getForeignProps()[$this->column] ?? '';

        $localEntries = GeneralUtility::trimExplode(',', $localValue, true);
        $foreignEntries = GeneralUtility::trimExplode(',', $foreignValue, true);

        $values = array_unique(array_filter(array_merge($localEntries, $foreignEntries)));

        foreach ($values as $value) {
            $position = strrpos($value, '_');
            if (false === $position) {
                continue;
            }
            $table = substr($value, 0, $position);
            if (in_array($table, $this->tables)) {
                $id = substr($value, $position + 1);

                $demands->addJoin($this->mmTable, $table, $this->additionalWhere, $this->selectField, $id, $record);
            }
        }
    }
}
