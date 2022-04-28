<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Resolver;

use In2code\In2publishCore\Component\TcaHandling\Demands;
use In2code\In2publishCore\Domain\Model\Record;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_filter;
use function array_merge;
use function array_unique;
use function strrpos;
use function substr;

class GroupMultiTableResolver implements Resolver
{
    protected string $column;

    public function __construct(string $column)
    {
        $this->column = $column;
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
            $id = substr($value, $position + 1);

            $demands->addSelect($table, '', 'uid', $id, $record);
        }
    }
}
