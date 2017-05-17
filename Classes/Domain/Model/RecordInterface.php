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

/**
 * RecordInterface
 */
interface RecordInterface
{
    const RECORD_STATE_UNCHANGED = 'unchanged';
    const RECORD_STATE_CHANGED = 'changed';
    const RECORD_STATE_ADDED = 'added';
    const RECORD_STATE_DELETED = 'deleted';
    const RECORD_STATE_MOVED = 'moved';
    const RECORD_STATE_MOVED_AND_CHANGED = 'moved-and-changed';

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
    );

    /**
     * @return string
     */
    public function getState();

    /**
     * @param string $state
     * @return RecordInterface
     */
    public function setState($state);

    /**
     * @return array
     */
    public function getLocalProperties();

    /**
     * @param string $propertyName
     * @return bool
     */
    public function hasLocalProperty($propertyName);

    /**
     * Returns a specific local property by name or NULL if it is not set
     *
     * @param string $propertyName
     * @return mixed
     */
    public function getLocalProperty($propertyName);

    /**
     * @param array $localProperties
     * @return RecordInterface
     */
    public function setLocalProperties(array $localProperties);

    /**
     * @return array
     */
    public function getForeignProperties();

    /**
     * @param string $propertyName
     * @return bool
     */
    public function hasForeignProperty($propertyName);

    /**
     * Returns a specific foreign property by name or NULL if it is not set
     *
     * @param string $propertyName
     * @return mixed
     */
    public function getForeignProperty($propertyName);

    /**
     * @param array $foreignProperties
     * @return RecordInterface
     */
    public function setForeignProperties(array $foreignProperties);

    /**
     * @return RecordInterface
     */
    public function setDirtyProperties();

    /**
     * @return mixed
     */
    public function getDirtyProperties();

    /**
     * @return RecordInterface
     */
    public function calculateState();

    /**
     * Returns an identifier unique in the records table.
     *
     * @return @return int|string
     */
    public function getIdentifier();

    /**
     * @param string $side
     * @param array $properties
     * @return $this
     */
    public function setPropertiesBySideIdentifier($side, array $properties);

    /**
     * @param string $side
     * @param string $propertyName
     * @return mixed
     */
    public function getPropertyBySideIdentifier($side, $propertyName);

    /**
     * @param string $propertyName
     * @return mixed
     */
    public function getAdditionalProperty($propertyName);

    /**
     * @param string $propertyName
     * @param mixed $propertyValue
     * @return RecordInterface
     */
    public function addAdditionalProperty($propertyName, $propertyValue);

    /**
     * @return string
     */
    public function getTableName();

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
    public function getMergedProperty($propertyName);

    /**
     * @return RecordInterface[][]
     */
    public function getRelatedRecords();

    /**
     * NOTICE: This will not work if debug.disableParentRecords is disabled!
     *
     * @return RecordInterface|null
     */
    public function getParentPageRecord();

    /**
     * Returns the parent record object or null if this is a root record
     * NOTICE: This will not work if debug.disableParentRecords is disabled!
     *
     * @return RecordInterface|null
     */
    public function getParentRecord();

    /**
     * @return bool
     */
    public function isChanged();

    /**
     * @param RecordInterface[] $relatedRecordsFlat
     * @return RecordInterface[]
     */
    public function addChangedRelatedRecordsRecursive($relatedRecordsFlat = array());

    /**
     * Returns the given records from the list of related records if the relation is direct.
     * The record is not removed recursively.
     *
     * @param RecordInterface $record
     * @return RecordInterface Returns always itself
     */
    public function removeRelatedRecord(RecordInterface $record);

    /**
     * Check if there is a local record
     *
     * @return bool
     */
    public function localRecordExists();

    /**
     * Check if there is a foreign record
     *
     * @return bool
     */
    public function foreignRecordExists();

    /**
     * @param string $table
     * @param string $property
     * @param mixed $value
     * @return RecordInterface[]
     */
    public function getRelatedRecordByTableAndProperty($table, $property, $value);

    /**
     * @param RecordInterface $record
     * @return mixed
     */
    public function addRelatedRecord(RecordInterface $record);

    /**
     * Adds a bunch of records
     *
     * @param RecordInterface[] $relatedRecords
     * @return RecordInterface
     */
    public function addRelatedRecords(array $relatedRecords);

    /**
     * @return bool
     */
    public function isParentRecordLocked();

    /**
     * Returns if this record or children record has changed in any way if added or changed or deleted
     *
     * @param array $alreadyVisited
     * @return bool
     */
    public function isChangedRecursive(array &$alreadyVisited = array());

    /**
     * @param RecordInterface $parentRecord
     * @return Record
     */
    public function setParentRecord(RecordInterface $parentRecord);

    /**
     * @return mixed
     */
    public function getColumnsTca();

    /**
     * @param string $propertyName
     * @return bool
     */
    public function hasAdditionalProperty($propertyName);

    /**
     * @param string $side
     * @return array
     */
    public function getPropertiesBySideIdentifier($side);

    /**
     * Prohibits changing this records parent record (prohibits changing parents of moved records)
     *
     * @return void
     */
    public function lockParentRecord();
}
