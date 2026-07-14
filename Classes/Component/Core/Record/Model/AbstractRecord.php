<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

use Generator;
use In2code\In2publishCore\Component\Core\Reason\Reasons;
use In2code\In2publishCore\Component\Core\Record\Iterator\IterationControls\SkipChildren;
use In2code\In2publishCore\Component\Core\Record\Iterator\IterationControls\StopIteration;
use In2code\In2publishCore\Component\Core\Record\Iterator\RecordIterator;
use In2code\In2publishCore\Component\Core\Record\Model\Extension\RecordExtensionTrait;
use In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable;
use LogicException;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_diff_key;
use function array_flip;
use function array_keys;
use function array_pop;
use function array_slice;
use function count;
use function implode;

use const PHP_EOL;

/** @SuppressWarnings(PHPMD.CouplingBetweenObjects) */
abstract class AbstractRecord implements Record
{
    use RecordExtensionTrait;

    // Initialize this in your constructor
    protected array $localProps;
    // Initialize this in your constructor
    protected array $foreignProps;
    protected string $state;
    /**
     * @var array<Dependency>
     */
    protected array $dependencies = [];
    /**
     * @var array<string, array<Record>>
     */
    protected array $children = [];
    /**
     * @var array<Record>
     */
    protected array $parents = [];
    /**
     * @var array<Record>
     */
    protected array $translations = [];
    protected ?Record $translationParent = null;
    /** @var array<string> */
    protected array $ignoredProps = [];
    /** @var array{reasons: Reasons} */
    protected array $rtc = [];

    public function getLocalProps(): array
    {
        return $this->localProps;
    }

    public function setLocalProps(array $localProps): void
    {
        $this->localProps = $localProps;
    }

    public function addLocalProp(string $prop, $value): void
    {
        $this->localProps[$prop] = $value;
    }

    public function getForeignProps(): array
    {
        return $this->foreignProps;
    }

    public function setForeignProps(array $foreignProps): void
    {
        $this->foreignProps = $foreignProps;
    }

    /**
     * @return scalar
     */
    public function getProp(string $prop)
    {
        return $this->localProps[$prop] ?? $this->foreignProps[$prop] ?? null;
    }

    public function getPropsBySide(string $side): array
    {
        switch ($side) {
            case Record::LOCAL:
                return $this->localProps;
            case Record::FOREIGN:
                return $this->foreignProps;
        }
        throw new LogicException("Side $side is unknown", 7470106618);
    }

    public function addChild(Record $record): void
    {
        $this->children[$record->getClassification()][$record->getId()] = $record;
        $record->addParent($this);
    }

    public function removeChild(Record $record): void
    {
        $classification = $record->getClassification();
        unset($this->children[$classification][$record->getId()]);
        if (empty($this->children[$classification])) {
            unset($this->children[$classification]);
        }
        $record->removeParent($this);
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function addParent(Record $parentRecord): void
    {
        $this->parents[] = $parentRecord;
    }

    public function removeParent(Record $record): void
    {
        foreach (array_keys($this->parents, $record) as $idx) {
            unset($this->parents[$idx]);
        }
    }

    public function getParents(): array
    {
        return $this->parents;
    }

    public function setTranslationParent(Record $translationParent): void
    {
        if (null !== $this->translationParent) {
            throw new LogicException('Can not add more than one translation parent', 2232766893);
        }
        $this->translationParent = $translationParent;
    }

    public function getTranslationParent(): ?Record
    {
        return $this->translationParent;
    }

    public function addTranslation(Record $childRecord): void
    {
        $language = $childRecord->getLanguage();
        $this->translations[$language][$childRecord->getId()] = $childRecord;
        $childRecord->setTranslationParent($this);
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function isChanged(): bool
    {
        return $this->localProps !== $this->foreignProps;
    }

    protected function calculateChangedProps(): array
    {
        $ignoredProps = array_flip($this->ignoredProps);
        $relevantLocalProps = array_diff_key($this->localProps, $ignoredProps);
        $relevantForeignProps = array_diff_key($this->foreignProps, $ignoredProps);
        return array_keys(ArrayUtility::arrayDiffAssocRecursive($relevantLocalProps, $relevantForeignProps));
    }

    protected function calculateState(): string
    {
        $localRecordExists = [] !== $this->localProps;
        $foreignRecordExists = [] !== $this->foreignProps;

        if ($localRecordExists && !$foreignRecordExists) {
            return Record::S_ADDED;
        }
        if (!$localRecordExists && $foreignRecordExists) {
            return Record::S_DELETED;
        }

        $isSoftDeleted = false;
        $deleteField = $GLOBALS['TCA'][$this->getClassification()]['ctrl']['delete'] ?? null;
        if (null !== $deleteField) {
            $isSoftDeleted = $this->localProps[$deleteField] ?? null;
            if ($isSoftDeleted && ($this->foreignProps[$deleteField] ?? null)) {
                $isSoftDeleted = false;
            }
        }
        if ($isSoftDeleted) {
            return Record::S_SOFT_DELETED;
        }
        $changedProps = $this->getChangedProps();
        if (empty($changedProps)) {
            $movedIndicatorFields = [];
            if (isset($GLOBALS['TCA'][$this->getClassification()])) {
                $movedIndicatorFields[] = 'pid';
            }

            $sortByField = $GLOBALS['TCA'][$this->getClassification()]['ctrl']['sortby'] ?? null;
            // tx_news sets sortby to an empty string
            if (!empty($sortByField)) {
                $movedIndicatorFields[] = $sortByField;
            }

            foreach ($movedIndicatorFields as $movedIndicatorField) {
                $localValue = $this->localProps[$movedIndicatorField] ?? null;
                $foreignValue = $this->foreignProps[$movedIndicatorField] ?? null;
                if ($localValue !== $foreignValue) {
                    return Record::S_MOVED;
                }
            }
            return Record::S_UNCHANGED;
        }

        return Record::S_CHANGED;
    }

    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @inheritDoc
     */
    public function getStateRecursive(): string
    {
        $state = $this->getState();
        if ($state !== Record::S_UNCHANGED) {
            return $state;
        }
        $recordState = Record::S_UNCHANGED;
        $recordIterator = new RecordIterator();
        $recordIterator->recurse($this, function (Record $record) use (&$recordState) {
            if ($record->getClassification() === 'pages' && $record !== $this) {
                throw new SkipChildren();
            }
            if ($record->getClassification() === 'pages') {
                return;
            }
            if ($record->getState() !== Record::S_UNCHANGED) {
                $recordState = Record::S_CHANGED;
                throw new StopIteration();
            }
        });
        return $recordState;
    }

    public function calculateDependencies(): array
    {
        return [];
    }

    public function getLanguage(): int
    {
        return 0;
    }

    public function getTransOrigPointer(): int
    {
        return 0;
    }

    public function getChangedProps(): array
    {
        return $this->calculateChangedProps();
    }

    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getAllDependencies(array &$visited = []): Generator
    {
        if (isset($visited[$this->getClassification()][$this->getId()])) {
            return [];
        }
        $visited[$this->getClassification()][$this->getId()] = true;
        yield from $this->dependencies;
        foreach ($this->children as $classification => $children) {
            if ('pages' !== $classification) {
                foreach ($children as $child) {
                    yield from $child->getAllDependencies($visited);
                }
            }
        }
    }

    public function getDependencyTree(array &$visited = [], array &$dependencyTree = []): array
    {
        $classification = $this->getClassification();
        $id = $this->getId();

        if (isset($visited[$classification][$id])) {
            return [];
        }
        $visited[$classification][$id] = true;

        $dependencyTree[$classification][$id]['dependencies'] = $this->dependencies;

        $count = count($this->children);
        if (isset($this->children['pages'])) {
            --$count;
        }
        if ($count > 0) {
            $dependencyTree[$classification][$id]['children'] = [];
        }

        foreach ($this->children as $childClassification => $children) {
            if ('pages' !== $childClassification) {
                foreach ($children as $child) {
                    $subtree = &$dependencyTree[$classification][$id]['children'];
                    $child->getDependencyTree($visited, $subtree);
                }
            }
        }
        return $dependencyTree;
    }

    private const LLL_PREFIX = 'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.';

    private const GROUPED_LABEL_MAP = [
        self::LLL_PREFIX . 'requires_published_page.existing'
            => self::LLL_PREFIX . 'grouped.requires_published_page.existing',
        self::LLL_PREFIX . 'requires_published_page.enablecolumns'
            => self::LLL_PREFIX . 'grouped.requires_published_page.enablecolumns',
        self::LLL_PREFIX . 'requires_translation_parent.existing'
            => self::LLL_PREFIX . 'grouped.requires_translation_parent.existing',
        self::LLL_PREFIX . 'requires_translation_parent.consistent_existence'
            => self::LLL_PREFIX . 'grouped.requires_translation_parent.consistent_existence',
        self::LLL_PREFIX . 'requires_translation_parent.enablecolumns'
            => self::LLL_PREFIX . 'grouped.requires_translation_parent.enablecolumns',
        self::LLL_PREFIX . 'shortcut_record'
            => self::LLL_PREFIX . 'grouped.shortcut_record',
    ];

    /**
     * @noinspection PhpUnused (Used in View)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getUnfulfilledDependenciesHumanReadableRecursively(): array
    {
        $dependencyTree = $this->getDependencyTree();
        /** @var array<array<Dependency>> $flattened */
        $flattened = [];
        $this->flattenDependencyTree($dependencyTree, $flattened);

        $grouped = [];
        foreach ($flattened as $dependencies) {
            foreach ($dependencies as $dependency) {
                if (!$dependency->isSupersededByUnfulfilledDependency() && !$dependency->isFulfilled()) {
                    $groupKey = $dependency->getClassification()
                        . '|' . $dependency->getPropertiesAsUidOrString()
                        . '|' . $dependency->getLabel();
                    $grouped[$groupKey][] = $dependency;
                }
            }
        }

        $result = [];
        foreach ($grouped as $dependencies) {
            $first = $dependencies[0];

            if (count($dependencies) <= 1) {
                $result[] = $first->getReasonsHumanReadable();
                continue;
            }

            // Collect all unique source record names
            $sourceNames = [];
            foreach ($dependencies as $dep) {
                $name = $dep->getSourceRecordName();
                if (!isset($sourceNames[$name])) {
                    $sourceNames[$name] = true;
                }
            }
            $sourceNames = array_keys($sourceNames);
            $affectedString = $this->formatAffectedRecordsList($sourceNames);

            $groupedMessage = null;
            $groupedLabel = self::GROUPED_LABEL_MAP[$first->getLabel()] ?? null;
            if ($groupedLabel !== null) {
                $targetName = $first->getTargetRecordName();
                $groupedMessage = LocalizationUtility::translate($groupedLabel, null, [$targetName]);
            }

            if (!empty($groupedMessage)) {
                $affectedLabel = LocalizationUtility::translate(
                    self::LLL_PREFIX . 'grouped.affected_records',
                    null,
                    [$affectedString],
                ) ?? 'Affected records: ' . $affectedString;
                $result[] = $groupedMessage . PHP_EOL . $affectedLabel;
            } else {
                $result[] = $first->getReasonsHumanReadable() . PHP_EOL
                    . (LocalizationUtility::translate(
                        self::LLL_PREFIX . 'grouped.affected_records',
                        null,
                        [$affectedString],
                    ) ?? 'Affected records: ' . $affectedString);
            }
        }

        return $result;
    }

    private function formatAffectedRecordsList(array $names): string
    {
        $maxDisplay = 3;
        $total = count($names);
        // Only truncate when hiding 3+ names; for 1-2 remaining just show them all
        if ($total > $maxDisplay + 2) {
            $displayed = array_slice($names, 0, $maxDisplay);
            $remaining = $total - $maxDisplay;
            $list = '"' . implode('", "', $displayed) . '"';
            return LocalizationUtility::translate(
                self::LLL_PREFIX . 'grouped.and_more',
                null,
                [$list, $remaining],
            ) ?? $list . ' and ' . $remaining . ' more';
        }
        return '"' . implode('", "', $names) . '"';
    }

    /**
     * @param array<Dependency> $dependencyTree
     */
    public function flattenDependencyTree(array $dependencyTree, array &$flattened, array &$parents = []): void
    {
        foreach ($dependencyTree as $classification => $identifiers) {
            foreach ($identifiers as $identifier => $structure) {
                $parents[] = "$classification [$identifier]";
                if (!empty($structure['children'])) {
                    $this->flattenDependencyTree($structure['children'], $flattened, $parents);
                }
                if (!empty($structure['dependencies'])) {
                    $flattened[implode(' / ', $parents)] = $structure['dependencies'];
                }
                array_pop($parents);
            }
        }
    }

    public function isPublishable(): bool
    {
        return !$this->hasUnfulfilledDependenciesRecursively()
            && !$this->hasReasonsWhyTheRecordIsNotPublishable();
    }

    public function getReasonsWhyTheRecordIsNotPublishableHumanReadable(): array
    {
        $string = [];
        foreach ($this->getReasonsWhyTheRecordIsNotPublishable()->getAll() as $reason) {
            $string[] = "{$this->getClassification()} [{$this->getId()}] -> $reason";
        }
        return $string;
    }

    public function hasReasonsWhyTheRecordIsNotPublishable(): bool
    {
        return !$this->getReasonsWhyTheRecordIsNotPublishable()->isEmpty();
    }

    public function getReasonsWhyTheRecordIsNotPublishable(): Reasons
    {
        if (isset($this->rtc['reasons'])) {
            return $this->rtc['reasons'];
        }
        $event = new CollectReasonsWhyTheRecordIsNotPublishable($this);
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $eventDispatcher->dispatch($event);
        return $this->rtc['reasons'] = $event->getReasons();
    }

    public function hasUnfulfilledDependenciesRecursively(): bool
    {
        if (isset($this->rtc['hasUnfulfilledDependenciesRecursively'])) {
            return $this->rtc['hasUnfulfilledDependenciesRecursively'];
        }
        /** @var array<Dependency> $allDependencies */
        $allDependencies = $this->getAllDependencies();
        foreach ($allDependencies as $dependency) {
            if (!$dependency->isFulfilled() && !$dependency->canBeFulfilledBy($this)) {
                return $this->rtc['hasUnfulfilledDependenciesRecursively'] = true;
            }
        }
        return $this->rtc['hasUnfulfilledDependenciesRecursively'] = false;
    }

    public function isPublishableIgnoringUnreachableDependencies(): bool
    {
        $beUser = $this->getBackendUser();
        if ($beUser->isAdmin()) {
            return $this->isPublishable();
        }
        $reasons = $this->getReasonsWhyTheRecordIsNotPublishable();
        if (!$reasons->isEmpty()) {
            return false;
        }
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->BE_USER = $beUser;

        $allDependencies = $this->getAllDependencies();
        /** @var Dependency $dependency */
        foreach ($allDependencies as $dependency) {
            if (!$dependency->isFulfilled() && !$dependency->canBeFulfilledBy($this)) {
                if (!$dependency->isReachable()) {
                    continue;
                }
                return false;
            }
        }
        return true;
    }

    public function __toString(): string
    {
        return $this->getClassification() . ' [' . $this->getId() . ']';
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
