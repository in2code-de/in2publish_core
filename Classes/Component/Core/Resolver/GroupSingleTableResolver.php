<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\Demands;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_filter;
use function array_merge;
use function strrpos;
use function substr;

class GroupSingleTableResolver extends AbstractResolver
{
    protected string $column;
    protected string $foreignTable;
    private const VALUE_SEPARATOR = ',';
    private const TABLE_SEPARATOR = '_';

    public function configure(string $column, string $foreignTable): void
    {
        $this->column = $column;
        $this->foreignTable = $foreignTable;
    }

    public function getTargetTables(): array
    {
        return [$this->foreignTable];
    }

    public function resolve(Demands $demands, Record $record): void
    {
        $localValue = $record->getLocalProps()[$this->column] ?? '';
        $foreignValue = $record->getForeignProps()[$this->column] ?? '';

        $localEntries = is_string($localValue)
            ? GeneralUtility::trimExplode(self::VALUE_SEPARATOR, $localValue, true)
            : [];
        $foreignEntries = is_string($foreignValue)
            ? GeneralUtility::trimExplode(self::VALUE_SEPARATOR, $foreignValue, true)
            : [];

        $values = array_filter(array_merge($localEntries, $foreignEntries));
        foreach ($values as $value) {
            if ((string)$value === (string)(int)$value) {
                $demands->addDemand(
                    new SelectDemand($this->foreignTable, '', 'uid', $value, $record)
                );
                continue;
            }

            $position = strrpos($value, self::TABLE_SEPARATOR);
            if ($position === false) {
                continue;
            }

            $table = substr($value, 0, $position);
            if ($table !== $this->foreignTable) {
                continue;
            }

            $id = substr($value, $position + 1);
            $demands->addDemand(new SelectDemand($this->foreignTable, '', 'uid', $id, $record));
        }
    }
}
