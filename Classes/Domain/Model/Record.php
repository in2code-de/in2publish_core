<?php
namespace In2code\In2publishCore\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Domain\Service\TcaService;
use In2code\In2publishCore\Utility\ConfigurationUtility;

/**
 * The most important class of this application. A Record is a Database
 * row and identifies itself by tableName + identifier (usually uid).
 * The combination of tableName + identifier is unique. Therefore a Record is
 * considered a singleton automatically. The RecordFactory takes care of
 * the singleton "implementation". The Pattern will break when a Record
 * gets instantiated without the use of the Factory
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
    protected $tableConfigurationArray = [];

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
    protected $parentRecord = null;

    /**
     * indicates if $this->parentRecord can be changed by the setter
     *
     * @var bool
     */
    protected $parentRecordIsLocked = false;

    /**
     * @var bool
     */
    protected $isParentRecordDisabled = false;

    /**
     * @param string $tableName
     * @param array $localProperties
     * @param array $foreignProperties
     * @param array $tableConfigurationArray
     * @param array $additionalProperties
     */
    public function __construct(
        $tableName,
        array $localProperties,
        array $foreignProperties,
        array $tableConfigurationArray,
        array $additionalProperties
    ) {
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
        $this->tableConfigurationArray = $tableConfigurationArray;
        $this->setDirtyProperties();
        $this->calculateState();
        $this->isParentRecordDisabled = $this->isParentRecordDisabled();
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     * @return RecordInterface
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPagesTable()
    {
        return $this->getTableName() === 'pages';
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     * @return RecordInterface
     */
    public function setState($state)
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
    public function isChanged()
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
     * @return string
     */
    public function getStateRecursive(array &$alreadyVisited = [])
    {
        if (!empty($alreadyVisited[$this->tableName])) {
            if (in_array($this->getIdentifier(), $alreadyVisited[$this->tableName])) {
                return static::RECORD_STATE_UNCHANGED;
            }
        }
        $alreadyVisited[$this->tableName][] = $this->getIdentifier();
        if (!$this->isChanged()) {
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
     * @return bool
     */
    public function isChangedRecursive(array &$alreadyVisited = [])
    {
        if ($this->getStateRecursive($alreadyVisited) !== static::RECORD_STATE_UNCHANGED) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getLocalProperties()
    {
        return $this->localProperties;
    }

    /**
     * Returns a specific local property by name or NULL if it is not set
     *
     * @param string $propertyName
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
     * @return bool
     */
    public function hasLocalProperty($propertyName)
    {
        return isset($this->localProperties[$propertyName]);
    }

    /**
     * @param array $localProperties
     * @return RecordInterface
     */
    public function setLocalProperties(array $localProperties)
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
     * @return bool
     */
    public function hasForeignProperty($propertyName)
    {
        return isset($this->foreignProperties[$propertyName]);
    }

    /**
     * @param string $side
     * @return array
     */
    public function getPropertiesBySideIdentifier($side)
    {
        switch ($side) {
            case 'local':
                return $this->getLocalProperties();
                break;
            case 'foreign':
                return $this->getForeignProperties();
                break;
            default:
                throw new \LogicException('Can not get properties from undefined side "' . $side . '"', 1475858502);
        }
    }

    /**
     * @param string $side
     * @param string $propertyName
     * @return mixed
     */
    public function getPropertyBySideIdentifier($side, $propertyName)
    {
        switch ($side) {
            case 'local':
                return $this->getLocalProperty($propertyName);
                break;
            case 'foreign':
                return $this->getForeignProperty($propertyName);
                break;
            default:
                throw new \LogicException(
                    'Can not get property "' . $propertyName . '" from undefined side "' . $side . '"',
                    1475858834
                );
        }
    }

    /**
     * @param array $foreignProperties
     * @return RecordInterface
     */
    public function setForeignProperties(array $foreignProperties)
    {
        $this->foreignProperties = $foreignProperties;
        $this->runtimeCache = [];
        return $this;
    }

    /**
     * @param string $side
     * @param array $properties
     * @return $this
     */
    public function setPropertiesBySideIdentifier($side, array $properties)
    {
        switch ($side) {
            case 'local':
                $this->setLocalProperties($properties);
                break;
            case 'foreign':
                $this->setForeignProperties($properties);
                break;
            default:
                throw new \LogicException('Can not set properties for undefined side "' . $side . '"', 1475857626);
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getDirtyProperties()
    {
        return $this->dirtyProperties;
    }

    /**
     * Set dirty properties of this record.
     *
     * @return RecordInterface
     */
    public function setDirtyProperties()
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
     * @return bool
     */
    protected function isDirtyProperty($propertyName)
    {
        return !array_key_exists($propertyName, $this->localProperties)
               || !array_key_exists($propertyName, $this->foreignProperties)
               || $this->localProperties[$propertyName] !== $this->foreignProperties[$propertyName];
    }

    /**
     * @return array
     */
    public function getAdditionalProperties()
    {
        return $this->additionalProperties;
    }

    /**
     * @param array $additionalProperties
     * @return RecordInterface
     */
    public function setAdditionalProperties(array $additionalProperties)
    {
        $this->additionalProperties = $additionalProperties;
        $this->runtimeCache = [];
        return $this;
    }

    /**
     * @param string $propertyName
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
     * @return bool
     */
    public function hasAdditionalProperty($propertyName)
    {
        return isset($this->additionalProperties[$propertyName]);
    }

    /**
     * @param $propertyName
     * @param $propertyValue
     * @return RecordInterface
     */
    public function addAdditionalProperty($propertyName, $propertyValue)
    {
        $this->additionalProperties[$propertyName] = $propertyValue;
        $this->runtimeCache = [];
        return $this;
    }

    /**
     * @return RecordInterface[][]
     */
    public function getRelatedRecords()
    {
        return $this->relatedRecords;
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
     * @return RecordInterface[]
     */
    public function getRelatedRecordByTableAndProperty($table, $property, $value)
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
     * @return void
     */
    public function addRelatedRecord(RecordInterface $record)
    {
        if (!$record->isParentRecordLocked()) {
            if (!($this->tableName === 'pages' && $record->getTableName() === 'pages')
                || (((int)$record->getMergedProperty('pid')) === ((int)$this->getIdentifier()))
            ) {
                if (!$this->isParentRecordDisabled) {
                    $record->setParentRecord($this);
                }
                $this->relatedRecords[$record->getTableName()][$record->getIdentifier()] = $record;
            }
        }
    }

    /**
     * Just add without any tests
     *
     * @param RecordInterface $record
     * @param string $tableName
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
     * @return RecordInterface
     */
    public function addRelatedRecords(array $relatedRecords)
    {
        foreach ($relatedRecords as $relatedRecord) {
            $this->addRelatedRecord($relatedRecord);
        }
        return $this;
    }

    /**
     * @param RecordInterface $record
     * @return RecordInterface
     */
    public function removeRelatedRecord(RecordInterface $record)
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
    public function hasDeleteField()
    {
        return TcaService::hasDeleteField($this->tableName);
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    public function getDeleteField()
    {
        return TcaService::getDeleteField($this->tableName);
    }

    /**
     * @return mixed
     * @codeCoverageIgnore
     */
    public function getColumnsTca()
    {
        return TcaService::getColumnsFor($this->tableName);
    }

    /**
     * @return RecordInterface
     */
    public function getParentRecord()
    {
        return $this->parentRecord;
    }

    /**
     * @param RecordInterface $parentRecord
     * @return RecordInterface
     */
    public function setParentRecord(RecordInterface $parentRecord)
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
    public function isParentRecordLocked()
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
                if (strpos($localValue, ',') !== false || strpos($foreignValue, ',') !== false) {
                    $localValueArray = explode(',', $localValue);
                    $foreignValueArray = explode(',', $foreignValue);
                    $value = implode(',', array_filter(array_merge($localValueArray, $foreignValueArray)));
                } else {
                    if ($localValue === '0' && $foreignValue !== '0') {
                        $value = $foreignValue;
                    } elseif ($localValue !== '0' && $foreignValue === '0') {
                        $value = $localValue;
                    } else {
                        if (strlen($localValue) > 0 && strlen($foreignValue) > 0) {
                            $value = implode(',', [$localValue, $foreignValue]);
                        } elseif (!$localValue && $foreignValue) {
                            $value = $foreignValue;
                        } else {
                            $value = $localValue;
                        }
                    }
                }
            } else {
                if (!$localValue && $foreignValue) {
                    $value = $foreignValue;
                } else {
                    $value = $localValue;
                }
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
    public function calculateState()
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
    public function isForeignRecordDeleted()
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
    public function isLocalRecordDeleted()
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
     * @return bool
     */
    protected function isRecordMarkedAsDeletedByProperties(array $properties)
    {
        if (isset($this->tableConfigurationArray['ctrl']['delete'])) {
            $deletedField = $this->tableConfigurationArray['ctrl']['delete'];
            if (isset($properties[$deletedField]) && ((bool)$properties[$deletedField]) === true) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isLocalRecordDisabled()
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
    public function isForeignRecordDisabled()
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
     * @return bool
     */
    protected function isRecordMarkedAsDisabledByProperties(array $properties)
    {
        if (!empty($this->tableConfigurationArray['ctrl']['enablecolumns']['disabled'])) {
            $disabledField = $this->tableConfigurationArray['ctrl']['enablecolumns']['disabled'];
            return (bool)$properties[$disabledField];
        }
        return false;
    }

    /**
     * Check if there is a foreign record
     *
     * @return bool
     */
    public function foreignRecordExists()
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
    public function localRecordExists()
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
     * @return bool
     */
    protected function isRecordRepresentByProperties(array $properties)
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
     * @return string
     */
    public static function createCombinedIdentifier(array $localProperties, array $foreignProperties)
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
     * @return array
     */
    public static function splitCombinedIdentifier($combinedIdentifier)
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
    public function getBreadcrumb()
    {
        $path = '/ ' . $this->tableName . ' [' . $this->getIdentifier() . ']';
        $path = $this->getRecordPath($this->parentRecord) . $path;
        return $path;
    }

    /**
     * @param RecordInterface|null $record
     * @return string
     */
    protected function getRecordPath(RecordInterface $record = null)
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
    public function getChangedRelatedRecordsFlat()
    {
        $relatedRecordsFlat = [];
        if ($this->isChanged()) {
            $relatedRecordsFlat[] = $this;
        }
        return $this->addChangedRelatedRecordsRecursive($relatedRecordsFlat);
    }

    /**
     * @return bool True if this record represents a page that can be viewed in the frontend
     */
    public function isLocalPreviewAvailable()
    {
        return $this->tableName === 'pages'
               && $this->getLocalProperty('doktype') < 200
               && $this->getLocalProperty('uid') > 0;
    }

    /**
     * @return bool True if this record represents a page that can be viewed in the frontend
     */
    public function isForeignPreviewAvailable()
    {
        return $this->tableName === 'pages'
               && $this->getForeignProperty('doktype') < 200
               && $this->getForeignProperty('uid') > 0;
    }

    /**
     * @param RecordInterface[] $relatedRecordsFlat
     * @return RecordInterface[]
     */
    public function addChangedRelatedRecordsRecursive($relatedRecordsFlat = [])
    {
        foreach ($this->getRelatedRecords() as $relatedRecords) {
            foreach ($relatedRecords as $relatedRecord) {
                if ($relatedRecord->isChanged()) {
                    if (!in_array($relatedRecord, $relatedRecordsFlat)) {
                        $relatedRecordsFlat[] = $relatedRecord;
                    }
                }
                $relatedRecordsFlat = $relatedRecord->addChangedRelatedRecordsRecursive($relatedRecordsFlat);
            }
        }
        return $relatedRecordsFlat;
    }

    /**
     * @return array
     * @codeCoverageIgnore
     */
    protected function getIgnoreFields()
    {
        return (array)ConfigurationUtility::getConfiguration('ignoreFieldsForDifferenceView.' . $this->tableName);
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    protected function isParentRecordDisabled()
    {
        return (bool)ConfigurationUtility::getConfiguration('debug.disableParentRecords');
    }
}
