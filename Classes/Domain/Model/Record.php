<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Model;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Service\Publishing\RunningRequestService;
use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use In2code\In2publishCore\Event\VoteIfRecordIsPublishable;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Service\Permission\PermissionService;
use LogicException;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_diff;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function spl_object_hash;
use function strpos;
use function trigger_error;
use function uasort;

use const E_USER_DEPRECATED;

/**
 * The most important class of this application. A Record is a Database
 * row and identifies itself by tableName + identifier (usually uid).
 * The combination of tableName + identifier is unique. Therefore a Record is
 * considered a singleton automatically. The RecordFactory takes care of
 * the singleton "implementation". The Pattern will break when a Record
 * gets instantiated without the use of the Factory
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Record implements RecordInterface
{
    /**
     * @var string
     */
    protected $tableName = 'pages';

    /**
     * Internal state, set by calculateState after object creation
     *
     * @var string
     */
    protected $state = self::RECORD_STATE_UNCHANGED;

    /**
     * @var array
     */
    protected $localProperties = [];

    /**
     * @var array
     */
    protected $foreignProperties = [];

    /**
     * Short said: difference between local and foreign properties
     *
     * @var array
     */
    protected $dirtyProperties = [];

    /**
     * e.g. the depth of the current record
     *
     * @var array
     */
    protected $additionalProperties = [];

    /**
     * records which are related to this record.
     *
     * @var RecordInterface[][]
     */
    protected $relatedRecords = [];

    /**
     * TableConfigurationArray of this record
     * $GLOBALS['TCA'][$this->tableName]
     *
     * @var array
     */
    protected $tca = [];

    /**
     * Internal (volatile) cache
     * used to store results of getters to improve performance
     *
     * @var array
     */
    protected $runtimeCache = [];

    /**
     * reference to the parent record. The parent record is
     * always the one which has a relation to this one
     *
     * will not be set if debug.disableParentRecords = TRUE
     * alteration of this value can be prohibited by setting
     * $this->parentRecordIsLocked = TRUE (or public setter)
     *
     * @var null|RecordInterface
     */
    protected $parentRecord;

    /**
     * indicates if $this->parentRecord can be changed by the setter
     *
     * @var bool
     */
    protected $parentRecordIsLocked = false;

    /**
     * @var bool
     */
    protected $isParentDisabled = false;

    /**
     * @var ConfigContainer
     */
    protected $configContainer;

    /**
     * @var RecordInterface[]
     */
    protected $translatedRecords = [];

    /**
     * @var int|string|null
     */
    protected $identifier;

    /**
     * @param string $tableName
     * @param array $localProperties
     * @param array $foreignProperties
     * @param array $tca
     * @param array $additionalProperties
     * @param int|string|null $identifier
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        string $tableName,
        array $localProperties,
        array $foreignProperties,
        array $tca,
        array $additionalProperties,
        $identifier = null
    ) {
        if ([false] === $localProperties) {
            $localProperties = [];
        }
        if ([false] === $foreignProperties) {
            $foreignProperties = [];
        }
        $this->configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        // Normalize the storage property to be always int, because FAL is inconsistent in this point
        if ('physical_folder' === $tableName) {
            if (isset($localProperties['storage'])) {
                $localProperties['storage'] = (int)$localProperties['storage'];
            }
            if (isset($foreignProperties['storage'])) {
                $foreignProperties['storage'] = (int)$foreignProperties['storage'];
            }
        }
        $this->tableName = $tableName;
        $this->additionalProperties = $additionalProperties;
        $this->setLocalProperties($localProperties);
        $this->setForeignProperties($foreignProperties);
        $this->tca = $tca;
        $this->setDirtyProperties();
        $this->calculateState();
        $this->isParentDisabled = $this->isParentDisabled();
        $this->identifier = $identifier;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function setTableName(string $tableName): RecordInterface
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function isPagesTable(): bool
    {
        return $this->getTableName() === 'pages';
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): RecordInterface
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Returns if this record has changed in any way
     *        if added or changed or deleted
     *
     * @return bool
     */
    public function isChanged(): bool
    {
        return $this->getState() !== static::RECORD_STATE_UNCHANGED;
    }

    /**
     * Get State of this or of children records
     *        Show changed status even if parent is
     *        unchanged but if children has changed
     *
     * Notice: This method does not returns RECORD_STATE_CHANGED
     * even if the first changed related record is added or deleted
     *
     * @param array $alreadyVisited
     *
     * @return string
     */
    public function getStateRecursive(array &$alreadyVisited = []): string
    {
        if (
            !empty($alreadyVisited[$this->tableName])
            && in_array($this->getIdentifier(), $alreadyVisited[$this->tableName])
        ) {
            return static::RECORD_STATE_UNCHANGED;
        }
        $alreadyVisited[$this->tableName][] = $this->getIdentifier();
        if (!$this->isChanged()) {
            foreach ($this->getTranslatedRecords() as $translatedRecord) {
                if ($translatedRecord->isChangedRecursive($alreadyVisited)) {
                    return static::RECORD_STATE_CHANGED;
                }
            }
            foreach ($this->getRelatedRecords() as $tableName => $relatedRecords) {
                if ($tableName === 'pages') {
                    continue;
                }
                foreach ($relatedRecords as $relatedRecord) {
                    if ($relatedRecord->isChangedRecursive($alreadyVisited)) {
                        return static::RECORD_STATE_CHANGED;
                    }
                }
            }
        }
        return $this->getState();
    }

    /**
     * Returns if this record or children record
     *        has changed in any way
     *        if added or changed or deleted
     *
     * @param array $alreadyVisited
     *
     * @return bool
     */
    public function isChangedRecursive(array &$alreadyVisited = []): bool
    {
        return $this->getStateRecursive($alreadyVisited) !== static::RECORD_STATE_UNCHANGED;
    }

    public function getLocalProperties(): array
    {
        return $this->localProperties;
    }

    /**
     * Returns a specific local property by name or NULL if it is not set
     *
     * @param string $propertyName
     *
     * @return mixed
     */
    public function getLocalProperty(string $propertyName)
    {
        if ($this->hasLocalProperty($propertyName)) {
            return $this->localProperties[$propertyName];
        }
        return null;
    }

    public function hasLocalProperty(string $propertyName): bool
    {
        return isset($this->localProperties[$propertyName]);
    }

    public function setLocalProperties(array $localProperties): RecordInterface
    {
        $this->localProperties = $localProperties;
        $this->runtimeCache = [];
        return $this;
    }

    public function getForeignProperties(): array
    {
        return $this->foreignProperties;
    }

    /**
     * Returns a specific foreign property by name or NULL if it is not set
     *
     * @param string $propertyName
     *
     * @return mixed
     */
    public function getForeignProperty(string $propertyName)
    {
        if ($this->hasForeignProperty($propertyName)) {
            return $this->foreignProperties[$propertyName];
        }
        return null;
    }

    public function hasForeignProperty(string $propertyName): bool
    {
        return isset($this->foreignProperties[$propertyName]);
    }

    public function getPropertiesBySideIdentifier(string $side): array
    {
        switch ($side) {
            case 'local':
                return $this->getLocalProperties();
            case 'foreign':
                return $this->getForeignProperties();
            default:
                throw new LogicException('Can not get properties from undefined side "' . $side . '"', 1475858502);
        }
    }

    /**
     * @param string $side
     * @param string $propertyName
     *
     * @return mixed
     */
    public function getPropertyBySideIdentifier(string $side, string $propertyName)
    {
        switch ($side) {
            case 'local':
                return $this->getLocalProperty($propertyName);
            case 'foreign':
                return $this->getForeignProperty($propertyName);
            default:
                throw new LogicException(
                    'Can not get property "' . $propertyName . '" from undefined side "' . $side . '"',
                    1475858834
                );
        }
    }

    public function setForeignProperties(array $foreignProperties): RecordInterface
    {
        $this->foreignProperties = $foreignProperties;
        $this->runtimeCache = [];
        return $this;
    }

    public function setPropertiesBySideIdentifier(string $side, array $properties): RecordInterface
    {
        switch ($side) {
            case 'local':
                $this->setLocalProperties($properties);
                break;
            case 'foreign':
                $this->setForeignProperties($properties);
                break;
            default:
                throw new LogicException('Can not set properties for undefined side "' . $side . '"', 1475857626);
        }
        return $this;
    }

    public function getDirtyProperties(): array
    {
        return $this->dirtyProperties;
    }

    /**
     * Set dirty properties of this record.
     *
     * @return RecordInterface
     */
    public function setDirtyProperties(): RecordInterface
    {
        // reset dirty properties first
        $this->dirtyProperties = [];
        $ignoreFields = $this->getIgnoreFields();
        if (!is_array($ignoreFields)) {
            $ignoreFields = [];
        }
        $propertyNames =
            array_diff(
                array_unique(
                    array_merge(
                        array_keys($this->localProperties),
                        array_keys($this->foreignProperties)
                    )
                ),
                $ignoreFields
            );

        foreach ($propertyNames as $propertyName) {
            if ($this->isDirtyProperty((string)$propertyName)) {
                $this->dirtyProperties[] = $propertyName;
            }
        }
        return $this;
    }

    public function isTranslationOriginal(RecordInterface $record): bool
    {
        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $pointerField = $tcaService->getTransOrigPointerField($this->getTableName());
        return !empty($pointerField) && $record->getIdentifier() === $this->getMergedProperty($pointerField);
    }

    /**
     * @param scalar $propertyName
     *
     * @return bool
     */
    protected function isDirtyProperty($propertyName): bool
    {
        return !array_key_exists($propertyName, $this->localProperties)
               || !array_key_exists($propertyName, $this->foreignProperties)
               || $this->localProperties[$propertyName] !== $this->foreignProperties[$propertyName];
    }

    public function getAdditionalProperties(): array
    {
        return $this->additionalProperties;
    }

    public function setAdditionalProperties(array $additionalProperties): RecordInterface
    {
        $this->additionalProperties = $additionalProperties;
        $this->runtimeCache = [];
        return $this;
    }

    /**
     * @param string $propertyName
     *
     * @return mixed
     */
    public function getAdditionalProperty(string $propertyName)
    {
        if ($this->hasAdditionalProperty($propertyName)) {
            return $this->additionalProperties[$propertyName];
        }
        return null;
    }

    public function hasAdditionalProperty(string $propertyName): bool
    {
        return isset($this->additionalProperties[$propertyName]);
    }

    public function addAdditionalProperty(string $propertyName, $propertyValue): RecordInterface
    {
        $this->additionalProperties[$propertyName] = $propertyValue;
        $this->runtimeCache = [];
        return $this;
    }

    /**
     * @return RecordInterface[][]
     */
    public function getRelatedRecords(): array
    {
        return $this->relatedRecords;
    }

    /**
     * @return RecordInterface[]
     */
    public function getTranslatedRecords(): array
    {
        return $this->translatedRecords;
    }

    public function addTranslatedRecord(RecordInterface $record): void
    {
        $this->translatedRecords[$record->getIdentifier()] = $record;
    }

    /**
     * NOTICE: This will not work if debug.disableParentRecords is disabled!
     *
     * @return RecordInterface|null
     */
    public function getParentPageRecord(): ?RecordInterface
    {
        if ($this->parentRecord instanceof RecordInterface) {
            if ('pages' === $this->parentRecord->getTableName()) {
                return $this->parentRecord;
            }
            return $this->parentRecord->getParentPageRecord();
        }
        return null;
    }

    /**
     * @param string $table
     * @param string $property
     * @param mixed $value
     *
     * @return RecordInterface[]
     */
    public function getRelatedRecordByTableAndProperty(string $table, string $property, $value): array
    {
        $relatedRecords = [];
        if (isset($this->relatedRecords[$table]) && is_array($this->relatedRecords[$table])) {
            foreach ($this->relatedRecords[$table] as $record) {
                if (
                    ($record->hasLocalProperty($property) && $record->getLocalProperty($property) === $value)
                    || ($record->hasForeignProperty($property) && $record->getForeignProperty($property) === $value)
                ) {
                    $relatedRecords[$record->getIdentifier()] = $record;
                }
            }
        }
        return $relatedRecords;
    }

    /**
     * adds a record to the related records, if parentRecord is unlocked
     * when parentRecord is locked nothing will happen
     *
     * @param RecordInterface $record
     *
     * @return void
     */
    public function addRelatedRecord(RecordInterface $record): void
    {
        if ($record->localRecordExists() || $record->foreignRecordExists()) {
            if (!$record->isParentRecordLocked()) {
                // If both records are from 'pages' the added record must be directly attached to this record by `pid`.
                // Ignore the foreign `pid`. It differs only when the record was moved but the record will be shown
                // beneath its new parent anyway.
                if (
                    !($this->isPagesTable() && $record->isPagesTable())
                    || $record->getSuperordinatePageIdentifier() === $this->getIdentifier()
                    || $this->isTranslationOriginal($record)
                ) {
                    if (!$this->isParentDisabled) {
                        $record->setParentRecord($this);
                    }
                    $this->relatedRecords[$record->getTableName()][$record->getIdentifier()] = $record;
                }
            }
        }
    }

    /**
     * Just add without any tests
     *
     * @param RecordInterface $record
     * @param string $tableName
     *
     * @return void
     */
    public function addRelatedRecordRaw(RecordInterface $record, string $tableName = 'pages'): void
    {
        $this->relatedRecords[$tableName][] = $record;
    }

    /**
     * Adds a bunch of records
     *
     * @param RecordInterface[] $relatedRecords
     *
     * @return RecordInterface
     */
    public function addRelatedRecords(array $relatedRecords): RecordInterface
    {
        foreach ($relatedRecords as $relatedRecord) {
            $this->addRelatedRecord($relatedRecord);
        }
        return $this;
    }

    public function removeRelatedRecord(RecordInterface $record): RecordInterface
    {
        $tableName = $record->getTableName();
        $identifier = $record->getIdentifier();
        if (isset($this->relatedRecords[$tableName][$identifier])) {
            unset($this->relatedRecords[$tableName][$identifier]);
        }
        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function hasDeleteField(): bool
    {
        return !empty($GLOBALS['TCA'][$this->tableName]['ctrl']['delete']);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDeleteField(): string
    {
        return $GLOBALS['TCA'][$this->tableName]['ctrl']['delete'] ?? '';
    }

    public function getParentRecord(): ?RecordInterface
    {
        return $this->parentRecord;
    }

    public function setParentRecord(RecordInterface $parentRecord): RecordInterface
    {
        if ($this->parentRecordIsLocked === false) {
            $this->parentRecord = $parentRecord;
        }
        return $this;
    }

    public function lockParentRecord(): void
    {
        $this->parentRecordIsLocked = true;
    }

    public function unlockParentRecord(): void
    {
        $this->parentRecordIsLocked = false;
    }

    public function isParentRecordLocked(): bool
    {
        return $this->parentRecordIsLocked;
    }

    /**
     * @return int|string
     */
    public function getIdentifier()
    {
        if (null !== $this->identifier) {
            return $this->identifier;
        }

        if ('physical_folder' === $this->tableName) {
            return $this->getMergedProperty('uid');
        }

        if ($this->hasLocalProperty('uid')) {
            return (int)$this->getLocalProperty('uid');
        }

        if ($this->hasForeignProperty('uid')) {
            return (int)$this->getForeignProperty('uid');
        }

        $combinedIdentifier = static::createCombinedIdentifier($this->localProperties, $this->foreignProperties);
        if ($combinedIdentifier !== '') {
            return $combinedIdentifier;
        }

        return 0;
    }

    /**
     * Get a property from both local and foreign of this Record.
     * 1. If a property does not exist on local, foreign is used and vice versa
     * 2. If a property exists on both sides and is the same the property is returned
     * 3. If local and foreign properties differ they are returned based on var type
     *      INT: local is returned
     *      ARRAY: both arrays merged
     *      STRING: strings concatenated with a comma
     *
     * @param $propertyName
     *
     * @return mixed
     */
    public function getMergedProperty($propertyName)
    {
        if ($this->hasLocalProperty($propertyName)) {
            $localValue = $this->getLocalProperty($propertyName);
        } else {
            return $this->getForeignProperty($propertyName);
        }
        if ($this->hasForeignProperty($propertyName)) {
            $foreignValue = $this->getForeignProperty($propertyName);
        } else {
            return $this->getLocalProperty($propertyName);
        }
        if ($localValue !== $foreignValue) {
            if (is_array($localValue) || is_array($foreignValue)) {
                $value = array_merge((array)$localValue, (array)$foreignValue);
            } elseif (is_string($localValue) || is_string($foreignValue)) {
                $localString = (string)$localValue;
                $foreignString = (string)$foreignValue;

                if (strpos($localString, ',') !== false || strpos($foreignString, ',') !== false) {
                    $localValueArray = explode(',', $localString);
                    $foreignValueArray = explode(',', $foreignString);
                    $value = implode(',', array_filter(array_merge($localValueArray, $foreignValueArray)));
                } elseif ($localString === '0' && $foreignString !== '0') {
                    $value = $foreignValue;
                } elseif ($localString !== '0' && $foreignString === '0') {
                    $value = $localValue;
                } elseif ($localString !== '' && $foreignString !== '') {
                    $value = implode(',', [$localString, $foreignString]);
                } elseif (!$localString && $foreignString) {
                    $value = $foreignValue;
                } else {
                    $value = $localValue;
                }
            } elseif (!$localValue && $foreignValue) {
                $value = $foreignValue;
            } else {
                $value = $localValue;
            }
        } else {
            $value = $localValue;
        }
        return $value;
    }

    /**
     * Sets this Records state depending on the local and foreign properties
     */
    public function calculateState(): void
    {
        if (
            'sys_file' === $this->tableName
            && !isset($this->additionalProperties['recordDatabaseState'])
            && $this->hasLocalProperty('identifier')
            && $this->hasForeignProperty('identifier')
            && $this->getLocalProperty('identifier') !== $this->getForeignProperty('identifier')
        ) {
            $this->setState(static::RECORD_STATE_MOVED);
            return;
        }
        if ($this->localRecordExists() && $this->foreignRecordExists()) {
            if ($this->isLocalRecordDeleted() && !$this->isForeignRecordDeleted()) {
                $this->setState(RecordInterface::RECORD_STATE_DELETED);
            } elseif (count($this->dirtyProperties) > 0) {
                if (
                    $this->state === RecordInterface::RECORD_STATE_MOVED
                    && isset($this->additionalProperties['recordDatabaseState'])
                ) {
                    $this->setState(RecordInterface::RECORD_STATE_MOVED_AND_CHANGED);
                } else {
                    $this->setState(RecordInterface::RECORD_STATE_CHANGED);
                }
            } else {
                $this->setState(RecordInterface::RECORD_STATE_UNCHANGED);
            }
        } elseif ($this->localRecordExists() && !$this->foreignRecordExists()) {
            $this->setState(RecordInterface::RECORD_STATE_ADDED);
        } elseif (!$this->localRecordExists() && $this->foreignRecordExists()) {
            $this->setState(RecordInterface::RECORD_STATE_DELETED);
        } else {
            $this->setState(RecordInterface::RECORD_STATE_UNCHANGED);
        }
    }

    public function isForeignRecordDeleted(): bool
    {
        if (!isset($this->runtimeCache['isForeignRecordDeleted'])) {
            $this->runtimeCache['isForeignRecordDeleted'] = $this->isRecordMarkedAsDeletedByProperties(
                $this->foreignProperties
            );
        }
        return $this->runtimeCache['isForeignRecordDeleted'];
    }

    public function isLocalRecordDeleted(): bool
    {
        if (!isset($this->runtimeCache['isLocalRecordDeleted'])) {
            $this->runtimeCache['isLocalRecordDeleted'] = $this->isRecordMarkedAsDeletedByProperties(
                $this->localProperties
            );
        }
        return $this->runtimeCache['isLocalRecordDeleted'];
    }

    protected function isRecordMarkedAsDeletedByProperties(array $properties): bool
    {
        if (isset($this->tca['ctrl']['delete'])) {
            $deletedField = $this->tca['ctrl']['delete'];
            if (isset($properties[$deletedField]) && ((bool)$properties[$deletedField]) === true) {
                return true;
            }
        }
        return false;
    }

    public function isLocalRecordDisabled(): bool
    {
        if (!isset($this->runtimeCache['isLocalRecordDisabled'])) {
            $this->runtimeCache['isLocalRecordDisabled'] = $this->isRecordMarkedAsDisabledByProperties(
                $this->localProperties
            );
        }
        return $this->runtimeCache['isLocalRecordDisabled'];
    }

    public function isForeignRecordDisabled(): bool
    {
        if (!isset($this->runtimeCache['isForeignRecordDisabled'])) {
            $this->runtimeCache['isForeignRecordDisabled'] = $this->isRecordMarkedAsDisabledByProperties(
                $this->foreignProperties
            );
        }
        return $this->runtimeCache['isForeignRecordDisabled'];
    }

    protected function isRecordMarkedAsDisabledByProperties(array $properties): bool
    {
        if (!empty($this->tca['ctrl']['enablecolumns']['disabled'])) {
            $disabledField = $this->tca['ctrl']['enablecolumns']['disabled'];
            return (bool)$properties[$disabledField];
        }
        return false;
    }

    /**
     * Check if there is a foreign record
     */
    public function foreignRecordExists(): bool
    {
        if (!isset($this->runtimeCache['foreignRecordExists'])) {
            $this->runtimeCache['foreignRecordExists'] = $this->isRecordRepresentByProperties($this->foreignProperties);
        }
        return $this->runtimeCache['foreignRecordExists'];
    }

    /**
     * Check if there is a local record
     */
    public function localRecordExists(): bool
    {
        if (!isset($this->runtimeCache['localRecordExists'])) {
            $this->runtimeCache['localRecordExists'] = $this->isRecordRepresentByProperties($this->localProperties);
        }
        return $this->runtimeCache['localRecordExists'];
    }

    /**
     * Checks if the given property array represents an existing Record
     */
    protected function isRecordRepresentByProperties(array $properties): bool
    {
        if ($this->tableName === 'folders') {
            return !empty($properties['name']);
        }

        if (
            empty($properties)
            || (array_key_exists(0, $properties) && false === $properties[0])
        ) {
            return false;
        }

        if (
            (isset($properties['uid']) && $properties['uid'] > 0)
            || (!empty($properties['uid_local']) && !empty($properties['uid_foreign']))
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param array $localProperties
     * @param array $foreignProperties
     * @param array<string>|null $idFields
     * @return string
     */
    public static function createCombinedIdentifier(
        array $localProperties,
        array $foreignProperties,
        array $idFields = null
    ): string {
        if (null !== $idFields) {
            foreach ([$localProperties, $foreignProperties] as $properties) {
                $identity = [];
                foreach ($idFields as $idField) {
                    if (!isset($properties[$idField])) {
                        continue 2;
                    }
                    $identity[$idField] = $properties[$idField];
                }
                return json_encode($identity);
            }
            return '';
        }
        foreach ([$localProperties, $foreignProperties] as $properties) {
            if (isset($properties['uid_local'], $properties['uid_foreign'])) {
                if (isset($properties['sorting'])) {
                    return $properties['uid_local'] . ',' . $properties['uid_foreign'] . ',' . $properties['sorting'];
                }
                return $properties['uid_local'] . ',' . $properties['uid_foreign'];
            }
        }
        return '';
    }

    /**
     * @param string $combinedIdentifier
     * @return array<string, int>
     */
    public static function splitCombinedIdentifier(string $combinedIdentifier): array
    {
        if ('' === $combinedIdentifier) {
            return [];
        }
        if ($combinedIdentifier[0] === '{') {
            return json_decode($combinedIdentifier, true);
        }
        $identifierArray = explode(',', $combinedIdentifier);
        $count = count($identifierArray);
        if (3 === $count) {
            return [
                'uid_local' => $identifierArray[0],
                'uid_foreign' => $identifierArray[1],
                'sorting' => $identifierArray[2],
            ];
        }
        if (2 === $count) {
            return [
                'uid_local' => $identifierArray[0],
                'uid_foreign' => $identifierArray[1],
            ];
        }
        return [];
    }

    public function sortRelatedRecords(string $tableName, callable $compareFunction): void
    {
        if (!empty($this->relatedRecords[$tableName]) && is_array($this->relatedRecords[$tableName])) {
            uasort($this->relatedRecords[$tableName], $compareFunction);
        }
    }

    public function getBreadcrumb(): string
    {
        $path = '';
        $record = $this;
        do {
            $path = '/ ' . $record->tableName . ' [' . $record->getIdentifier() . '] ' . $path;
        } while ($record->tableName !== 'pages' && $record = $record->parentRecord);
        return rtrim($path, ' ');
    }

    /** @return RecordInterface[] */
    public function getChangedRelatedRecordsFlat(): array
    {
        $relatedRecordsFlat = [];
        if ($this->isChanged()) {
            $relatedRecordsFlat[] = $this;
        }
        return array_values($this->addChangedRelatedRecordsRecursive($relatedRecordsFlat));
    }

    /**
     * @return bool True if this record represents a page that can be viewed in the frontend
     */
    public function isLocalPreviewAvailable(): bool
    {
        return $this->tableName === 'pages'
               && $this->getLocalProperty('doktype') < 200
               && $this->getLocalProperty('uid') > 0;
    }

    /**
     * @return bool True if this record represents a page that can be viewed in the frontend
     */
    public function isForeignPreviewAvailable(): bool
    {
        return $this->tableName === 'pages'
               && $this->getForeignProperty('doktype') < 200
               && $this->getForeignProperty('uid') > 0;
    }

    /**
     * @param RecordInterface[] $relatedRecordsFlat
     * @param array $done
     *
     * @return RecordInterface[]
     */
    public function addChangedRelatedRecordsRecursive(array $relatedRecordsFlat = [], array &$done = []): array
    {
        $relatedRecordsPerTable = $this->getRelatedRecords();
        unset($relatedRecordsPerTable['pages']);
        foreach ($relatedRecordsPerTable as $relatedRecords) {
            foreach ($relatedRecords as $relatedRecord) {
                $splObjectHash = spl_object_hash($relatedRecord);
                if (!isset($relatedRecordsFlat[$splObjectHash]) && $relatedRecord->isChanged()) {
                    $relatedRecordsFlat[$splObjectHash] = $relatedRecord;
                }
                if (!isset($done[$splObjectHash])) {
                    $done[$splObjectHash] = true;
                    $relatedRecordsFlat = $relatedRecord->addChangedRelatedRecordsRecursive($relatedRecordsFlat, $done);
                }
            }
        }
        return $relatedRecordsFlat;
    }

    /**
     * @return mixed
     *
     * @codeCoverageIgnore
     */
    protected function getIgnoreFields()
    {
        return $this->configContainer->get('ignoreFieldsForDifferenceView.' . $this->tableName);
    }

    /**
     * @return bool
     *
     * @codeCoverageIgnore
     */
    protected function isParentDisabled(): bool
    {
        return $this->configContainer->get('debug.disableParentRecords');
    }

    public function getPageIdentifier(): int
    {
        if ($this->isPagesTable()) {
            $identifier = $this->getL10nParentIdentifier();
            if (null === $identifier) {
                $identifier = $this->getIdentifier();
                if (is_string($identifier)) {
                    $identifier = (int)explode(',', $identifier)[0];
                }
            }
            return $identifier;
        }
        if ($this->hasLocalProperty('pid')) {
            return (int)$this->getLocalProperty('pid');
        }

        if ($this->hasForeignProperty('pid')) {
            return (int)$this->getForeignProperty('pid');
        }
        return 0;
    }

    public function getSuperordinatePageIdentifier(): int
    {
        if ($this->isPagesTable() && $this->isTranslation()) {
            return $this->getL10nParentIdentifier() ?? 0;
        }

        if ($this->hasLocalProperty('pid')) {
            return (int)$this->getLocalProperty('pid');
        }

        if ($this->hasForeignProperty('pid')) {
            return (int)$this->getForeignProperty('pid');
        }
        return 0;
    }

    protected function getL10nParentIdentifier(): ?int
    {
        if ($this->isTranslation()) {
            $tcaService = GeneralUtility::makeInstance(TcaService::class);
            $l10nPointer = $tcaService->getTransOrigPointerField($this->tableName);
            if (!empty($l10nPointer) && array_key_exists($l10nPointer, $this->localProperties)) {
                return $this->localProperties[$l10nPointer];
            }
        }
        return null;
    }

    public function isTranslation(): bool
    {
        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $languageField = $tcaService->getLanguageField($this->tableName);
        return !empty($languageField)
               && array_key_exists($languageField, $this->localProperties)
               && $this->localProperties[$languageField] > 0;
    }

    public function getRecordLanguage(): int
    {
        $language = 0;

        $tcaService = GeneralUtility::makeInstance(TcaService::class);
        $languageField = $tcaService->getLanguageField($this->tableName);
        if (!empty($languageField) && array_key_exists($languageField, $this->localProperties)) {
            $language = $this->localProperties[$languageField];
        }

        return $language;
    }

    public function isPublishable(): bool
    {
        if (!$this->isChangedRecursive()) {
            return false;
        }
        $runningRequestService = GeneralUtility::makeInstance(RunningRequestService::class);
        if ($runningRequestService->isPublishingRequestRunningForThisRecord($this)) {
            return false;
        }

        $permissionService = GeneralUtility::makeInstance(PermissionService::class);
        if (!$permissionService->isUserAllowedToPublish()) {
            return false;
        }
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
        $event = new VoteIfRecordIsPublishable($this->tableName, $this->getIdentifier());
        $eventDispatcher->dispatch($event);
        return $event->getVotingResult();
    }

    public function isRemovedFromLocalDatabase(): bool
    {
        return $this->isForeignRecordDeleted() && !$this->isRecordRepresentByProperties($this->localProperties);
    }

    /**
     * @deprecated Please use <code>$tcaProcessingService->getCompatibleTcaColumns($record->getTableName())</code>
     *     instead.
     * @codeCoverageIgnore
     */
    public function getColumnsTca(): array
    {
        trigger_error(
            'The method \In2code\In2publishCore\Domain\Model\Record::getColumnsTca is deprecated and will be removed in in2publish_core v11. Please use "$tcaProcessingService->getCompatibleTcaColumns($record->getTableName())" instead.',
            E_USER_DEPRECATED
        );
        return GeneralUtility::makeInstance(TcaProcessingService::class)->getCompatibleTcaColumns($this->tableName);
    }
}
