<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

use Closure;
use In2code\In2publishCore\Component\Core\Reason\Reason;
use In2code\In2publishCore\Component\Core\Reason\Reasons;
use In2code\In2publishCore\Component\Core\RecordCollection;

use function implode;

use const PHP_EOL;

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

    public function getClassification(): string
    {
        return $this->classification;
    }

    public function getProperties(): array
    {
        return $this->properties;
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
                    [$this->classification, $propertiesString]
                )
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
            $localProps = $record->getLocalProps();
            $foreignProps = $record->getForeignProps();
            $enableFields = $GLOBALS['TCA'][$this->classification]['ctrl']['enablecolumns'];
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
        return implode(PHP_EOL, $this->reasons->map(static fn(Reason $reason) => $reason->getReadableLabel()));
    }
}
