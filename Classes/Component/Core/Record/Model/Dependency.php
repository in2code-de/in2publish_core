<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

use Closure;
use In2code\In2publishCore\Component\Core\Reason\Reason;
use In2code\In2publishCore\Component\Core\Reason\Reasons;
use In2code\In2publishCore\Component\Core\RecordCollection;
use TYPO3\CMS\Core\DataHandling\DataHandler;

use function array_keys;
use function count;
use function implode;

use const PHP_EOL;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Dependency
{
    public const REQ_EXISTING = 'existing';
    public const REQ_ENABLECOLUMNS = 'enablecolumns';
    public const REQ_FULL_PUBLISHED = 'fully_published';
    private Record $record;
    private string $classification;
    private array $properties;
    private string $label;
    private Closure $labelArgumentsFactory;
    private string $requirement;
    private Reasons $reasons;
    private RecordCollection $selectedRecords;
    /**
     * Superseded dependencies must be fulfilled first.
     *
     * @var array<Dependency>
     */
    private array $supersededBy = [];

    public function __construct(
        Record $record,
        string $classification,
        array $properties,
        string $requirement,
        string $label,
        Closure $labelArgumentsFactory
    ) {
        $this->record = $record;
        $this->classification = $classification;
        $this->properties = $properties;
        $this->label = $label;
        $this->labelArgumentsFactory = $labelArgumentsFactory;
        $this->requirement = $requirement;
        $this->reasons = new Reasons();
        $this->selectedRecords = new RecordCollection();
    }

    public function addSupersedingDependency(Dependency $dependency): void
    {
        $this->supersededBy[] = $dependency;
    }

    public function getRecord(): Record
    {
        return $this->record;
    }

    public function getClassification(): string
    {
        return $this->classification;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getRequirement(): string
    {
        return $this->requirement;
    }

    public function getPropertiesAsUidOrString(): string
    {
        if (count($this->properties) === 1 && isset($this->properties['uid'])) {
            return (string)$this->properties['uid'];
        }

        $propertiesString = [];
        foreach ($this->properties as $property => $value) {
            $propertiesString[] = $property . '=' . $value;
        }
        return implode(', ', $propertiesString);
    }

    public function fulfill(RecordCollection $recordIndex): void
    {
        $records = $recordIndex->getRecordsByProperties($this->classification, $this->properties);
        if (empty($records)) {
            $propertiesString = [];
            foreach ($this->properties as $key => $value) {
                $propertiesString[] = $key . '=' . $value;
            }
            $propertiesString = implode(', ', $propertiesString);
            $this->reasons->addReason(
                new Reason(
                    'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.missing_dependency',
                    [$this->classification, $propertiesString],
                ),
            );
            return;
        }
        foreach ($records as $record) {
            $this->selectedRecords->addRecord($record);
            if (!$this->recordMatchesRequirements($record)) {
                $this->reasons->addReason(new Reason($this->label, ($this->labelArgumentsFactory)($record)));
            }
        }
    }

    protected function recordMatchesRequirements(Record $record): bool
    {
        if (self::REQ_FULL_PUBLISHED === $this->requirement) {
            return $record->getState() === Record::S_UNCHANGED;
        }
        if (self::REQ_EXISTING === $this->requirement) {
            return $record->getState() !== Record::S_ADDED;
        }
        if (self::REQ_ENABLECOLUMNS === $this->requirement) {
            $state = $record->getState();
            if ($state === Record::S_UNCHANGED) {
                return true;
            }
            if ($state === Record::S_ADDED || $state === Record::S_DELETED) {
                return false;
            }

            if ($this->record instanceof AbstractDatabaseRecord) {
                $enableFields = array_keys($this->record->getInheritedEnableColumnsWithLabels());
            } else {
                $enableFields = $GLOBALS['TCA'][$this->classification]['ctrl']['enablecolumns'] ?? [];
            }
            $localProps = $record->getLocalProps();
            $foreignProps = $record->getForeignProps();
            foreach ($enableFields as $enableField) {
                if ($localProps[$enableField] !== $foreignProps[$enableField]) {
                    return false;
                }
            }
        }
        return true;
    }

    public function isFulfilled(): bool
    {
        if ($this->isSupersededByUnfulfilledDependency()) {
            return false;
        }
        return $this->reasons->isEmpty();
    }

    public function isSupersededByUnfulfilledDependency(): bool
    {
        return !empty($this->supersededBy) && !$this->areSupersededDependenciesFulfilled();
    }

    protected function areSupersededDependenciesFulfilled(): bool
    {
        foreach ($this->supersededBy as $dependency) {
            if (!$dependency->isFulfilled()) {
                return false;
            }
        }
        return true;
    }

    public function canBeFulfilledBy(Record $record): bool
    {
        if ($this->selectedRecords->isEmpty()) {
            return false;
        }
        foreach ($this->selectedRecords as $selectedRecord) {
            if (
                $record !== $selectedRecord
                && !$this->recordMatchesRequirements($selectedRecord)
            ) {
                return false;
            }
        }
        return true;
    }

    public function getReasonsHumanReadable(): string
    {
        return implode(PHP_EOL, $this->reasons->map(static fn (Reason $reason) => $reason->getReadableLabel()));
    }

    /**
     * fulfill() must be called before this method. The recordIndex passed to fulfill() must contain any dependency
     * target record including those outside the record tree. This is done in the RecordIndex before calling fulfill().
     * @see \In2code\In2publishCore\Component\Core\RecordIndex::processDependencies
     *
     * When fulfill() did not select any record, the records do not exist in the database. If they exist in the
     *     database
     * but not in the record tree, they would have been found by RecordIndex::processDependencies prior to calling
     * fulfill()
     *
     * So at this point, all existing relation targets are contained in $this->selectedRecords. If dependency targets
     * are missing, they do not block publishing. Therefore, dependencies to non-existent records (= empty
     * $this->selectedRecords) are ignored silently. It is intended, that $this->selectedRecords->are() returns empty if
     * there are no records in it.
     */
    public function isReachable(DataHandler $dataHandler): bool
    {
        $beUser = $dataHandler->BE_USER;
        if ($beUser->isAdmin()) {
            return true;
        }

        return $this->selectedRecords->are(static function (Record $record) use ($beUser, $dataHandler): bool {
            $language = $record->getLanguage();
            if (!$beUser->checkLanguageAccess($language)) {
                return false;
            }
            if ($record instanceof AbstractDatabaseRecord) {
                $table = $record->getClassification();
                if ($dataHandler->tableReadOnly($table)) {
                    return false;
                }
                if (!$dataHandler->checkModifyAccessList($table)) {
                    return false;
                }
                $pid = $record->getProp('pid');
                if (
                    (
                        ($GLOBALS['TCA'][$table]['ctrl']['rootLevel'] ?? false)
                        || 0 === $pid
                    )
                    && empty($GLOBALS['TCA'][$table]['ctrl']['security']['ignoreRootLevelRestriction'])
                ) {
                    return false;
                }
                $editLockField = $GLOBALS['TCA'][$table]['ctrl']['editlock'] ?? null;
                if (null !== $editLockField && $record->getProp($editLockField)) {
                    return false;
                }
                // If the PID is not null, the web mount restriction is
                // either ignored or the page must be within the web mount.
                if (
                    null !== $pid
                    && !($GLOBALS['TCA'][$table]['ctrl']['security']['ignoreWebMountRestriction'] ?? false)
                    && !$dataHandler->isInWebMount($pid)
                ) {
                    return false;
                }
            }
            return true;
        });
    }
}
