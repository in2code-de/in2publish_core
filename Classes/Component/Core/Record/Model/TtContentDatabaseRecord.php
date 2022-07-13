<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use function strrpos;
use function substr;

class TtContentDatabaseRecord extends DatabaseRecord
{
    public function calculateDependencies(): array
    {
        $dependencies = parent::calculateDependencies();
        if (($this->localProps['CType'] ?? null) === 'shortcut') {
            $referencedRecords = $this->localProps['records'];
            foreach ($this->resolveShortcutDependencies($referencedRecords) as $dependency) {
                $dependencies[] = $dependency;
            }
        }
        if (($this->foreignProps['CType'] ?? null) === 'shortcut') {
            $referencedRecords = $this->foreignProps['records'];
            foreach ($this->resolveShortcutDependencies($referencedRecords) as $dependency) {
                $dependencies[] = $dependency;
            }
        }

        return $dependencies;
    }

    /**
     * @return array<Dependency>
     */
    protected function resolveShortcutDependencies(string $records): array
    {
        $dependencies = [];
        $recordList = GeneralUtility::trimExplode(',', $records);
        foreach ($recordList as $record) {
            $position = strrpos($record, '_');
            if (false === $position) {
                continue;
            }
            $table = substr($record, 0, $position);
            $id = substr($record, $position + 1);

            $dependencies[] = new Dependency(
                $this,
                $table,
                ['uid' => $id],
                Dependency::REQ_FULL_PUBLISHED,
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.shortcut_record',
                static fn(Record $record): array => [$record->__toString()]
            );
        }

        return $dependencies;
    }
}
