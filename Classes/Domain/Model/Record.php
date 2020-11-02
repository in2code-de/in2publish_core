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
use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Service\Permission\PermissionService;
use LogicException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

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
use function is_null;
use function is_string;
use function spl_object_hash;
use function strlen;
use function strpos;
use function uasort;

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
     * @var RecordInterface
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
     * @param string $tableName
     * @param array $localProperties
     * @param array $foreignProperties
     * @param array $tca
     * @param array $additionalProperties
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(
        $tableName,
        array $localProperties,
        array $foreignProperties,
        array $tca,
        array $additionalProperties
    ) {
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
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     *
     * @return RecordInterface
     */
    public function setTableName($tableName): RecordInterface
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPagesTable(): bool
    {
        return $this->getTableName() === 'pages';
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @return RecordInterface
     */
    public function setState($state): RecordInterface
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
        if ($this->getState() !== static::RECORD_STATE_UNCHANGED) {
            return true;
        }
        return false;
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
        if (!empty($alreadyVisited[$this->tableName])) {
            if (in_array($this->getIdentifier(), $alreadyVisited[$this->tableName])) {
                return static::RECORD_STATE_UNCHANGED;
            }
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

    /**
     * @return array
     */
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
    public function getLocalProperty($propertyName)
    {
        if ($this->hasLocalProperty($propertyName)) {
            return $this->localProperties[$propertyName];
        }
        return null;
    }

    /**
     * @param string $propertyName
     *
     * @return bool
     */
    public function hasLocalProperty($propertyName): bool
    {
        return isset($this->localProperties[$propertyName]);
    }

    /**
     * @param array $localProperties
     *
     * @return RecordInterface
     */
    public function setLocalProperties(array $localProperties): RecordInterface
    {
        $this->localProperties = $localProperties;
        $this->runtimeCache = [];
        return $this;
    }

    /**
     * @return array
     */
    public function getForeignProperties()
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
    public function getForeignProperty($propertyName)
    {
        if ($this->hasForeignProperty($propertyName)) {
            return $this->foreignProperties[$propertyName];
        }
        return null;
    }

    /**
     * @param string $propertyName
     *
     * @return bool
     */
    public function hasForeignProperty($propertyName)
    {
        return isset($this->foreignProperties[$propertyName]);
    }

    /**
     * @param string $side
     *
     * @return array
     */
    public function getPropertiesBySideIdentifier($side)
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
    public function getPropertyBySideIdentifier($side, $propertyName)
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

    /**
     * @param array $foreignProperties
     *
     * @return RecordInterface
     */
    public function setForeignProperties(array $foreignProperties): RecordInterface
    {
        $this->foreignProperties = $foreignProperties;
        $this->runtimeCache = [];
        return $this;
    }

    /**
     * @param string $side
     * @param array $properties
     *
     * @return RecordInterface
     */
    public function setPropertiesBySideIdentifier($side, array $properties): RecordInterface
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

    /**
     * @return array
     */
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
            if ($this->isDirtyProperty($propertyName)) {
                $this->dirtyProperties[] = $propertyName;
            }
        }
        return $this;
    }

    /**
     * @param string $propertyName
     *
     * @return bool
     */
    protected function isDirtyProperty($propertyName): bool
    {
        return !array_key_exists($propertyName, $this->localProperties)
               || !array_key_exists($propertyName, $this->foreignProperties)
               || $this->localProperties[$propertyName] !== $this->foreignProperties[$propertyName];
    }

    /**
     * @return array
     */
    public function getAdditionalProperties(): array
    {
        return $this->additionalProperties;
    }

    /**
     * @param array $additionalProperties
     *
     * @return RecordInterface
     */
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
    public function getAdditionalProperty($propertyName)
    {
        if ($this->hasAdditionalProperty($propertyName)) {
            return $this->additionalProperties[$propertyName];
        }
        return null;
    }

    /**
     * @param string $propertyName
     *
     * @return bool
     */
    public function hasAdditionalProperty($propertyName): bool
    {
        return isset($this->additionalProperties[$propertyName]);
    }

    /**
     * @param $propertyName
     * @param $propertyValue
     *
     * @return RecordInterface
     */
    public function addAdditionalProperty($propertyName, $propertyValue): RecordInterface
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

    /**
     * @param RecordInterface $record
     * @return void
     */
    public function addTranslatedRecord(RecordInterface $record): void
    {
        $this->translatedRecords[$record->getIdentifier()] = $record;
    }

    /**
     * NOTICE: This will not work if debug.disableParentRecords is disabled!
     *
     * @return RecordInterface|null
     */
    public function getParentPageRecord()
    {
        if ($this->parentRecord instanceof RecordInterface) {
            if ('pages' === $this->parentRecord->getTableName()) {
                return $this->parentRecord;
            } else {
                return $this->parentRecord->getParentPageRecord();
            }
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
    public function getRelatedRecordByTableAndProperty($table, $property, $value): array
    {
        $relatedRecords = [];
        if (isset($this->relatedRecords[$table]) && is_array($this->relatedRecords[$table])) {
            foreach ($this->relatedRecords[$table] as $record) {
                if (($record->hasLocalProperty($property)
                     && $record->getLocalProperty($property) === $value)
                    || ($record->hasForeignProperty($property)
                        && $record->getForeignProperty($property) === $value)
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
    public function addRelatedRecord(RecordInterface $record)
    {
        if ($record->localRecordExists() || $record->foreignRecordExists()) {
            if (!$record->isParentRecordLocked()) {
                // If both records are from 'pages' the added record must be directly attached to this record by `pid`.
                // Ignore the foreign `pid`. It differs only when the record was moved but the record will be shown
                // beneath its new parent anyway.
                if (!($this->isPagesTable() && $record->isPagesTable())
                    || $record->getSuperordinatePageIdentifier() === $this->getIdentifier()
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
    public function addRelatedRecordRaw(RecordInterface $record, $tableName = 'pages')
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

    /**
     * @param RecordInterface $record
     *
     * @return RecordInterface
     */
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
     * @return bool
     * @codeCoverageIgnore
     */
    public function hasDeleteField(): bool
    {
        return TcaProcessingService::hasDeleteField($this->tableName);
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getDeleteField(): string
    {
        return TcaProcessingService::getDeleteField($this->tableName);
    }

    /**
     * @return mixed
     * @codeCoverageIgnore
     */
    public function getColumnsTca(): array
    {
        return TcaProcessingService::getColumnsFor($this->tableName);
    }

    /**
     * @return RecordInterface|null
     */
    public function getParentRecord()
    {
        return $this->parentRecord;
    }

    /**
     * @param RecordInterface $parentRecord
     *
     * @return RecordInterface
     */
    public function setParentRecord(RecordInterface $parentRecord): RecordInterface
    {
        if ($this->parentRecordIsLocked === false) {
            $this->parentRecord = $parentRecord;
        }
        return $this;
    }

    /**
     * @return void
     */
    public function lockParentRecord()
    {
        $this->parentRecordIsLocked = true;
    }

    /**
     * @return void
     */
    public function unlockParentRecord()
    {
        $this->parentRecordIsLocked = false;
    }

    /**
     * @return bool
     */
    public function isParentRecordLocked(): bool
    {
        return $this->parentRecordIsLocked;
    }

    /**
     * @return int|string
     */
    public function getIdentifier()
    {
        $uid = 0;
        if ('physical_folder' === $this->tableName) {
            return $this->getMergedProperty('uid');
        } elseif ($this->hasLocalProperty('uid')) {
            $uid = $this->getLocalProperty('uid');
        } elseif ($this->hasForeignProperty('uid')) {
            $uid = $this->getForeignProperty('uid');
        } else {
            $combinedIdentifier = static::createCombinedIdentifier($this->localProperties, $this->foreignProperties);
            if (strlen($combinedIdentifier) > 0) {
                return $combinedIdentifier;
            }
        }
        return (int)$uid;
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
        $localValue = null;
        $foreignValue = null;
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
                } elseif (strlen($localString) > 0 && strlen($foreignString) > 0) {
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
     *
     * @return void
     */
    public function calculateState(): void
    {
        if ($this->tableName === 'sys_file' && !isset($this->additionalProperties['recordDatabaseState'])) {
            if ($this->hasLocalProperty('identifier') && $this->hasForeignProperty('identifier')) {
                if ($this->localProperties['identifier'] !== $this->foreignProperties['identifier']) {
                    $this->setState(static::RECORD_STATE_MOVED);
                    return;
                }
            }
        }
        if ($this->localRecordExists() && $this->foreignRecordExists()) {
            if ($this->isLocalRecordDeleted() && !$this->isForeignRecordDeleted()) {
                $this->setState(RecordInterface::RECORD_STATE_DELETED);
            } elseif (count($this->dirtyProperties) > 0) {
                if ($this->state === RecordInterface::RECORD_STATE_MOVED
                    && isset($this->additionalProperties['recordDatabaseState'])) {
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

    /**
     * @return bool
     */
    public function isForeignRecordDeleted(): bool
    {
        if (!isset($this->runtimeCache['isForeignRecordDeleted'])) {
            $this->runtimeCache['isForeignRecordDeleted'] = $this->isRecordMarkedAsDeletedByProperties(
                $this->foreignProperties
            );
        }
        return $this->runtimeCache['isForeignRecordDeleted'];
    }

    /**
     * @return bool
     */
    public function isLocalRecordDeleted(): bool
    {
        if (!isset($this->runtimeCache['isLocalRecordDeleted'])) {
            $this->runtimeCache['isLocalRecordDeleted'] = $this->isRecordMarkedAsDeletedByProperties(
                $this->localProperties
            );
        }
        return $this->runtimeCache['isLocalRecordDeleted'];
    }

    /**
     * @param array $properties
     *
     * @return bool
     */
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

    /**
     * @return bool
     */
    public function isLocalRecordDisabled(): bool
    {
        if (!isset($this->runtimeCache['isLocalRecordDisabled'])) {
            $this->runtimeCache['isLocalRecordDisabled'] = $this->isRecordMarkedAsDisabledByProperties(
                $this->localProperties
            );
        }
        return $this->runtimeCache['isLocalRecordDisabled'];
    }

    /**
     * @return bool
     */
    public function isForeignRecordDisabled(): bool
    {
        if (!isset($this->runtimeCache['isForeignRecordDisabled'])) {
            $this->runtimeCache['isForeignRecordDisabled'] = $this->isRecordMarkedAsDisabledByProperties(
                $this->foreignProperties
            );
        }
        return $this->runtimeCache['isForeignRecordDisabled'];
    }

    /**
     * @param array $properties
     *
     * @return bool
     */
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
     *
     * @return bool
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
     *
     * @return bool
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
     *
     * @param array $properties
     *
     * @return bool
     */
    protected function isRecordRepresentByProperties(array $properties): bool
    {
        if ($this->tableName === 'folders') {
            if (!empty($properties['name'])) {
                return true;
            } else {
                return false;
            }
        }
        if (empty($properties) || (array_key_exists(0, $properties) && $properties[0] === false)) {
            return false;
        } elseif ((isset($properties['uid']) && $properties['uid'] > 0)
                  || (!empty($properties['uid_local']) && !empty($properties['uid_foreign']))
        ) {
            return true;
        }
        return false;
    }

    /**
     * @param array $localProperties
     * @param array $foreignProperties
     *
     * @return string
     */
    public static function createCombinedIdentifier(array $localProperties, array $foreignProperties): string
    {
        if (!empty($localProperties['uid_local']) && !empty($localProperties['uid_foreign'])) {
            return $localProperties['uid_local'] . ',' . $localProperties['uid_foreign'];
        } elseif (!empty($foreignProperties['uid_local']) && !empty($foreignProperties['uid_foreign'])) {
            return $foreignProperties['uid_local'] . ',' . $foreignProperties['uid_foreign'];
        }
        return '';
    }

    /**
     * @param $combinedIdentifier
     *
     * @return array
     */
    public static function splitCombinedIdentifier($combinedIdentifier): array
    {
        if (false === strpos($combinedIdentifier, ',')) {
            return [];
        } else {
            $identifierArray = explode(',', $combinedIdentifier);
            return [
                'uid_local' => $identifierArray[0],
                'uid_foreign' => $identifierArray[1],
            ];
        }
    }

    /**
     * @param $tableName
     * @param callable $compareFunction
     *
     * @return void
     */
    public function sortRelatedRecords($tableName, $compareFunction)
    {
        if (!empty($this->relatedRecords[$tableName]) && is_array($this->relatedRecords[$tableName])) {
            uasort($this->relatedRecords[$tableName], $compareFunction);
        }
    }

    /**
     * @return string
     */
    public function getBreadcrumb(): string
    {
        $path = '/ ' . $this->tableName . ' [' . $this->getIdentifier() . ']';
        $path = $this->getRecordPath($this->parentRecord) . $path;
        return $path;
    }

    /**
     * @param RecordInterface|null $record
     *
     * @return string
     */
    protected function getRecordPath(RecordInterface $record = null): string
    {
        $path = '';
        if (!is_null($record)) {
            $path = '/ ' . $record->getTableName() . ' [' . $record->getIdentifier() . '] ';
            $path = $this->getRecordPath($record->getParentRecord()) . $path;
        }

        return $path;
    }

    /**
     * @return RecordInterface[]
     */
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
    public function addChangedRelatedRecordsRecursive($relatedRecordsFlat = [], array &$done = []): array
    {
        foreach ($this->getRelatedRecords() as $relatedRecords) {
            foreach ($relatedRecords as $relatedRecord) {
                $splObjectHash = spl_object_hash($relatedRecord);
                if ($relatedRecord->isChanged()) {
                    if (!isset($relatedRecordsFlat[$splObjectHash])) {
                        $relatedRecordsFlat[$splObjectHash] = $relatedRecord;
                    }
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

    /**
     * @return int
     */
    public function getPageIdentifier(): int
    {
        if ($this->isPagesTable()) {
            $l10nParent = $this->getL10nParentIdentifier();
            if (null !== $l10nParent) {
                return $l10nParent;
            }
            return $this->getIdentifier();
        }
        if ($this->hasLocalProperty('pid')) {
            return (int)$this->getLocalProperty('pid');
        } elseif ($this->hasForeignProperty('pid')) {
            return (int)$this->getForeignProperty('pid');
        }
        return 0;
    }

    /**
     * @return int
     */
    public function getSuperordinatePageIdentifier(): int
    {
        if ($this->isPagesTable() && $this->isTranslation()) {
            return $this->getL10nParentIdentifier() ?? 0;
        }
        if ($this->hasLocalProperty('pid')) {
            return (int)$this->getLocalProperty('pid');
        } elseif ($this->hasForeignProperty('pid')) {
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
        $permissionService = GeneralUtility::makeInstance(PermissionService::class);
        if (!$permissionService->isUserAllowedToPublish()) {
            return false;
        }
        $signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $votes = $signalSlotDispatcher->dispatch(
            RecordInterface::class,
            'isPublishable',
            [['yes' => 0, 'no' => 0], $this->tableName, $this->getIdentifier()]
        );
        return $votes[0]['yes'] >= $votes[0]['no'];
    }
}
