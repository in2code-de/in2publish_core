<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Domain\Model\Record;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_filter;
use function array_merge;

class GroupSingleTableResolver implements Resolver
{
    protected string $column;
    protected string $foreignTable;

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

        $localEntries = GeneralUtility::trimExplode(',', $localValue, true);
        $foreignEntries = GeneralUtility::trimExplode(',', $foreignValue, true);

        $values = array_filter(array_merge($localEntries, $foreignEntries));
        foreach ($values as $value) {
            $demands->addSelect($this->foreignTable, '', 'uid', $value, $record);
        }
    }
}
