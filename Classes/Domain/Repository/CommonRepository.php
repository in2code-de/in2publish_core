<?php
namespace In2code\In2publishCore\Domain\Repository;

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

use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\NullRecord;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Service\ReplaceMarkersService;
use In2code\In2publishCore\Utility\ArrayUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use In2code\In2publishCore\Utility\FileUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

/**
 * CommonRepository - actions in foreign and local database
 *
 * This is, beside the Record Model, the second important class in this Extension
 *
 * What does this Repository
 *  - find Records by identifier
 *  - find Records by properties
 *  - find related Records to a Record by its TCA definition
 *
 * Important notice:
 *  This Repository does not simple fetch a local or a foreign Record,
 *  it fetches always both. Hence the Resulting Record object contains
 *  properties from both databases "local" and "foreign"
 *
 *  Any Record created by this Repository will be enriched with related Records
 *  if they are existing. This is achieved by recursion. The recursion is hidden
 *  between this Repository and the RecordFactory:
 *
 *    repository->findByIdentifier()
 *    '- factory->makeInstance()
 *       '- repository->enrichRecordWithRelatedRecords()
 *          '- repository->convertPropertyArraysToRecords()
 *             '- factory->makeInstance()
 *                '- continue as long as depth < maxDepth
 *
 *  this loop breaks in the factory when maximumRecursionDepth is reached
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CommonRepository extends BaseRepository
{
    /**
     * @var RecordFactory
     */
    protected $recordFactory;

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var DatabaseConnection
     */
    protected $localDatabase = null;

    /**
     * @var DatabaseConnection
     */
    protected $foreignDatabase = null;

    /**
     * Cache for skipped records
     *
     * @var array
     */
    protected $skipRecords = [];

    /**
     * @param DatabaseConnection $localDatabase
     * @param DatabaseConnection $foreignDatabase
     * @param string $tableName
     * @param string $identifierFieldName
     */
    public function __construct(
        DatabaseConnection $localDatabase,
        DatabaseConnection $foreignDatabase,
        $tableName,
        $identifierFieldName = 'uid'
    ) {
        parent::__construct();
        $this->recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $this->taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $this->identifierFieldName = $identifierFieldName;
        $this->localDatabase = $localDatabase;
        $this->foreignDatabase = $foreignDatabase;
        if ($foreignDatabase === null || !$foreignDatabase->isConnected()) {
            $this->foreignDatabase = $localDatabase;
        }
        $this->setTableName($tableName);
    }

    /**
     * Find and create a Record where the Records identifier equals $identifier
     * Returns exactly one Record.
     *
     * @param int $identifier
     * @param string $tableName
     * @return RecordInterface
     */
    public function findByIdentifier($identifier, $tableName = null)
    {
        if ($tableName !== null) {
            $this->tableName = $tableName;
        }
        if ($this->shouldSkipFindByIdentifier($identifier)) {
            return GeneralUtility::makeInstance(NullRecord::class, $tableName);
        }
        $local = $this->findPropertiesByProperty($this->localDatabase, $this->identifierFieldName, $identifier);
        $local = empty($local) ? [] : reset($local);
        $foreign = $this->findPropertiesByProperty($this->foreignDatabase, $this->identifierFieldName, $identifier);
        $foreign = empty($foreign) ? [] : reset($foreign);
        return $this->convertToRecord($local, $foreign);
    }

    /**
     * Finds and creates none or more Records in the current table name
     * where the propertyName (e.g. pid or tstamp) matches the given value.
     *
     * @param string $propertyName
     * @param mixed $propertyValue
     * @return RecordInterface[]
     */
    public function findByProperty($propertyName, $propertyValue)
    {
        if ($this->shouldSkipFindByProperty($propertyName, $propertyValue)) {
            return [];
        }
        if ($propertyName === 'uid'
            && $record = $this->recordFactory->getCachedRecord($this->tableName, $propertyValue)
        ) {
            return $record;
        }
        $localProperties = $this->findPropertiesByProperty($this->localDatabase, $propertyName, $propertyValue);
        $foreignProperties = $this->findPropertiesByProperty($this->foreignDatabase, $propertyName, $propertyValue);
        return $this->convertPropertyArraysToRecords($localProperties, $foreignProperties);
    }

    /**
     * Finds and creates none or more Records in the current table name
     * where the properties are matching.
     *
     * @param array $properties
     * @param bool $simulateRoot Simulate an existent root record to prevent filePostProcessing
     *  in the RecordFactory for each single Record
     *
     * @return RecordInterface[]
     */
    public function findByProperties(array $properties, $simulateRoot = false)
    {
        if ($simulateRoot) {
            $this->recordFactory->simulateRootRecord();
        }
        foreach ($properties as $propertyName => $propertyValue) {
            if ($this->shouldSkipFindByProperty($propertyName, $propertyValue)) {
                return [];
            }
        }
        if (isset($properties['uid'])
            && $record = $this->recordFactory->getCachedRecord($this->tableName, $properties['uid'])
        ) {
            return $record;
        }
        $localProperties = $this->findPropertiesByProperties($this->localDatabase, $properties);
        $foreignProperties = $this->findPropertiesByProperties($this->foreignDatabase, $properties);
        $records = $this->convertPropertyArraysToRecords($localProperties, $foreignProperties);
        if ($simulateRoot) {
            $this->recordFactory->endSimulation();
        }
        return $records;
    }

    /**
     * Fetches an array of property arrays (plural !!!) from
     * the given database connection where the column
     * "$propertyName" equals $propertyValue
     * Add table name
     *
     * @param DatabaseConnection $databaseConnection
     * @param string $tableName
     * @param string $propertyName
     * @param mixed $propertyValue
     * @param string $additionalWhere
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     * @param string $indexField
     * @return array
     */
    protected function findPropertiesByPropertyAndTablename(
        DatabaseConnection $databaseConnection,
        $tableName,
        $propertyName,
        $propertyValue,
        $additionalWhere = '',
        $groupBy = '',
        $orderBy = '',
        $limit = '',
        $indexField = 'uid'
    ) {
        $currentTableName = $this->tableName;
        $this->tableName = $tableName;
        $properties = $this->findPropertiesByProperty(
            $databaseConnection,
            $propertyName,
            $propertyValue,
            $additionalWhere,
            $groupBy,
            $orderBy,
            $limit,
            $indexField
        );
        $this->tableName = $currentTableName;
        return $properties;
    }

    /**
     * Find the last record by property and table name
     *
     * @param DatabaseConnection $databaseConnection
     * @param string $tableName
     * @param string $propertyName
     * @param mixed $propertyValue
     * @return array
     */
    public function findLastPropertiesByPropertyAndTableName(
        DatabaseConnection $databaseConnection,
        $tableName,
        $propertyName,
        $propertyValue
    ) {
        $properties = $this->findPropertiesByPropertyAndTablename(
            $databaseConnection,
            $tableName,
            $propertyName,
            $propertyValue,
            '',
            '',
            'uid desc',
            '1'
        );
        $firstKey = key($properties);
        if ($firstKey !== null) {
            return $properties[$firstKey];
        }
        return [];
    }

    /**
     * converts arrays of propertyArrays to records.
     * the key of each propertyArrays must be an UID
     *
     * this method ignores following property arrays:
     *      - deleted on both sides and identical
     *      - is deleted local and non existing on foreign
     *
     * structure:
     *  $properties['uid'] = array(
     *              'property_A' => 'value_A',
     *              'property_B' => 'value_B'
     *  );
     *
     * @param array $localProperties
     * @param array $foreignProperties
     * @return RecordInterface[]
     */
    protected function convertPropertyArraysToRecords(array $localProperties, array $foreignProperties)
    {
        $keysToIterate = array_unique(array_merge(array_keys($localProperties), array_keys($foreignProperties)));

        $foundRecords = [];

        foreach ($keysToIterate as $key) {
            if (strpos($key, ',') === false) {
                if (empty($localProperties[$key])) {
                    $propertyArray = $this->findPropertiesByProperty($this->localDatabase, 'uid', $key);
                    if (!empty($propertyArray[$key])) {
                        $localProperties[$key] = $propertyArray[$key];
                    }
                }
                if (empty($foreignProperties[$key])) {
                    $propertyArray = $this->findPropertiesByProperty($this->foreignDatabase, 'uid', $key);
                    if (!empty($propertyArray[$key])) {
                        $foreignProperties[$key] = $propertyArray[$key];
                        if ('sys_file_metadata' === $this->tableName
                            && isset($localProperties[$key]['file'])
                            && isset($foreignProperties[$key]['file'])
                            && (int)$localProperties[$key]['file'] !== (int)$foreignProperties[$key]['file']
                        ) {
                            // If the fixing of this relation results in a different related
                            // record we log it because it is very very very unlikely for
                            // sys_file_metadata to change their target sys_file entry
                            $this->logger->warning(
                                'Fixed possibly broken relation by replacing it with another possibly broken relation',
                                [
                                    'table' => $this->tableName,
                                    'key (UID)' => $key,
                                    'file_local' => $localProperties[$key]['file'],
                                    'file_foreign' => $foreignProperties[$key]['file'],
                                ]
                            );
                        }
                    }
                }
            }
            if (!$this->isIgnoredRecord((array)$localProperties[$key], (array)$foreignProperties[$key])) {
                $foundRecords[$key] = $this->convertToRecord(
                    (array)$localProperties[$key],
                    (array)$foreignProperties[$key]
                );
            }
        }

        return array_filter($foundRecords);
    }

    /**
     * Adds all related Records to the given Record
     * until maximum recursion depth is reached
     * Any related Record must be connected by valid TCA of the given one
     * Relations are only solved for the given record as "owning side"
     *
     * Records which relate on the given Record are not included,
     * they will have $record as their related record (from the other side)
     *
     * @param RecordInterface $record
     * @param array $excludedTableNames
     * @return RecordInterface
     */
    public function enrichRecordWithRelatedRecords(RecordInterface $record, array $excludedTableNames)
    {
        if ($this->shouldSkipSearchingForRelatedRecords($record)) {
            return $record;
        }
        $recordTableName = $record->getTableName();
        // keep the following extra line for debugging issues
        $columns = $record->getColumnsTca();
        foreach ($columns as $propertyName => $columnConfiguration) {
            if ($this->shouldSkipSearchingForRelatedRecordsByProperty($record, $propertyName, $columnConfiguration)) {
                continue;
            }
            $previousIdFieldName = $this->identifierFieldName;
            $this->identifierFieldName = 'uid';
            switch ($columnConfiguration['type']) {
                case 'select':
                    $relatedRecords = $this->fetchRelatedRecordsBySelect(
                        $columnConfiguration,
                        $record,
                        $propertyName,
                        $excludedTableNames
                    );
                    break;
                case 'inline':
                    $relatedRecords = $this->fetchRelatedRecordsByInline(
                        $columnConfiguration,
                        $recordTableName,
                        $record->getIdentifier(),
                        $excludedTableNames
                    );
                    break;
                case 'group':
                    $relatedRecords = $this->fetchRelatedRecordsByGroup(
                        $columnConfiguration,
                        $record,
                        $propertyName,
                        $excludedTableNames
                    );
                    break;
                case 'flex':
                    $relatedRecords = $this->fetchRelatedRecordsByFlexForm(
                        $record,
                        $propertyName,
                        $excludedTableNames,
                        $columnConfiguration
                    );
                    break;
                case 'input':
                    // fall through because fetch by RTE already supports "file:x" links
                case 'text':
                    // TODO: use some kind of merged property to check against changed foreign RTE relations
                    $relatedRecords = $this->fetchRelatedRecordsByRte(
                        $record->getLocalProperty($propertyName),
                        $excludedTableNames
                    );
                    break;
                default:
                    $relatedRecords = [];
            }
            $this->identifierFieldName = $previousIdFieldName;

            foreach ($relatedRecords as $index => $relatedRecord) {
                if (!($relatedRecord instanceof RecordInterface)) {
                    $this->logger->alert(
                        'Relation was resolved but result is not a record',
                        [
                            'tablename' => $record->getTableName(),
                            'uid' => $record->getIdentifier(),
                            'propertyName' => $propertyName,
                            'columnConfiguration' => $columnConfiguration,
                            'relatedRecordType' => gettype($relatedRecord),
                        ]
                    );
                    unset($relatedRecords[$index]);
                }
            }

            try {
                $record->addRelatedRecords($relatedRecords);
            } catch (\Exception $e) {
                $this->logger->emergency(
                    'Exception thrown while adding related record: ' . $e->getMessage(),
                    [
                        'code' => $e->getCode(),
                        'tablename' => $record->getTableName(),
                        'uid' => $record->getIdentifier(),
                        'propertyName' => $propertyName,
                        'columnConfiguration' => $columnConfiguration,
                        'relatedRecords' => $relatedRecords,
                    ]
                );
            }
        }

        try {
            list($record) = $this->signalSlotDispatcher->dispatch(
                CommonRepository::class,
                'afterRecordEnrichment',
                [$record]
            );
        } catch (InvalidSlotException $e) {
        } catch (InvalidSlotReturnException $e) {
        }
        return $record;
    }

    /**
     * If RTE (rtehtmlarea) is enabled for a records text property,
     * the complete string will be searched for image urls. These urls are
     * converted into sys_file_processedfile which have a related sys_file record.
     *
     * @param string $bodyText
     * @param array $excludedTableNames
     * @return array
     */
    protected function fetchRelatedRecordsByRte($bodyText, array $excludedTableNames)
    {
        $relatedRecords = [];
        // if RTE is enabled
        if (strpos($bodyText, 'src=') !== false) {
            // match and src tag
            preg_match_all('~src="([^\s]*)"~', $bodyText, $matches);
            // remove the "matched" portion from results
            // we only need the "matching" portion
            if (!empty($matches[1])) {
                $matches = $matches[1];
            }
            if (count($matches) > 0) {
                if (!in_array('sys_file_processedfile', $excludedTableNames)) {
                    $previousTableName = $this->replaceTableName('sys_file_processedfile');
                    foreach ($matches as $match) {
                        if (!empty($match)) {
                            // replace fileadmin if present. It has been replaced by the storage field (FAL)
                            if (strpos($match, 'fileadmin') === 0) {
                                $match = substr($match, 9);
                            }
                            $relatedRecords = array_merge($relatedRecords, $this->findByProperty('identifier', $match));
                        }
                    }
                    $this->tableName = $previousTableName;
                }
            }
        }
        if (strpos($bodyText, 'file:') !== false) {
            preg_match_all('~file:(\d+)~', $bodyText, $matches);
            if (!empty($matches[1])) {
                $matches = $matches[1];
            }
            $matches = array_filter($matches);
            if (count($matches) > 0) {
                if (!in_array('sys_file', $excludedTableNames)) {
                    $previousTableName = $this->replaceTableName('sys_file');
                    foreach ($matches as $match) {
                        $relatedRecords[] = $this->findByIdentifier($match);
                    }
                    $this->tableName = $previousTableName;
                }
            }
        }
        return $relatedRecords;
    }

    /**
     * finds and adds related records to pages. this is a special case, because any
     * related Record is found by its pid
     *
     * @param RecordInterface $record
     * @param array $excludedTableNames
     * @return RecordInterface
     */
    public function enrichPageRecord(RecordInterface $record, array $excludedTableNames)
    {
        if ($this->shouldSkipEnrichingPageRecord($record)) {
            return $record;
        }
        $recordIdentifier = $record->getIdentifier();
        foreach ($this->tcaService->getAllTableNames($excludedTableNames) as $tableName) {
            if ($this->shouldSkipSearchingForRelatedRecordByTable($record, $tableName)) {
                continue;
            }
            $previousTableName = $this->replaceTableName($tableName);
            $relatedRecords = $this->findByProperty('pid', $recordIdentifier);
            $record->addRelatedRecords($relatedRecords);
            $this->tableName = $previousTableName;
        }
        return $record;
    }

    /**
     * @param RecordInterface $record
     * @param array $columnConfiguration
     * @return string
     */
    protected function getFlexFormDefinitionSource(RecordInterface $record, array $columnConfiguration)
    {
        $dsArray = $columnConfiguration['ds'];
        if (!isset($columnConfiguration['ds_pointerField'])) {
            return $dsArray['default'];
        }
        $pointerFields = GeneralUtility::trimExplode(',', $columnConfiguration['ds_pointerField']);
        $pointerFieldsCount = count($pointerFields);
        if ($pointerFieldsCount === 2) {
            // stage wins! Only use local properties.
            // Usually "list_type"
            $firstPointerValue = $record->getLocalProperty($pointerFields[0]);
            // Usually "CType"
            $secondPointerValue = $record->getLocalProperty($pointerFields[1]);

            // Intentionally named and used this way (mainly for code sniffer :D)
            $possibleCombination = 'default';

            $possibleCombinations = [
                $firstPointerValue . ',' . $secondPointerValue,
                $firstPointerValue . ',*',
                '*,' . $secondPointerValue,
                $firstPointerValue,
                $possibleCombination,
            ];

            $definitionSource = null;

            foreach ($possibleCombinations as $possibleCombination) {
                if (!empty($dsArray[$possibleCombination])) {
                    $definitionSource = $dsArray[$possibleCombination];
                    break;
                }
            }
        } elseif ($pointerFieldsCount === 1) {
            $definitionSource = $dsArray[$record->getLocalProperty($pointerFields[0])];
        } else {
            $definitionSource = '';
        }
        return (string)$definitionSource;
    }

    /**
     * @param string $flexFormSource
     * @return string
     */
    protected function resolveFlexFormSource($flexFormSource)
    {
        $flexFormString = '';
        if (!is_string($flexFormSource)) {
            return $flexFormString;
        }
        if (substr($flexFormSource, 0, 5) == 'FILE:') {
            $flexFormSource = GeneralUtility::getFileAbsFileName(substr($flexFormSource, 5));
            if (is_file($flexFormSource)) {
                if (is_readable($flexFormSource)) {
                    $flexFormString = file_get_contents($flexFormSource);
                } else {
                    $this->logger->error('The FlexForm file ' . $flexFormSource . ' is not readable');
                    return $flexFormString;
                }
            } else {
                $this->logger->error('The FlexForm file ' . $flexFormSource . ' does not exist');
                return $flexFormString;
            }
        } else {
            $flexFormString = $flexFormSource;
        }
        return $flexFormString;
    }

    /**
     * TODO: Drop this whole monstrous except the FlexFormTools part upon dropping TYPO3 v7
     *
     * Get flex form configuration from file or reference
     *
     * @param RecordInterface $record
     * @param string $column
     * @param array $columnConfiguration
     * @return array|mixed
     */
    protected function getFlexFormDefinition(RecordInterface $record, $column, array $columnConfiguration)
    {
        if (method_exists(FlexFormTools::class, 'getDataStructureIdentifier')
            && isset($columnConfiguration['ds_pointerField'])
        ) {
            /** @var FlexFormTools $flexFormTools */
            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
            $dataStructIdentifier = $flexFormTools->getDataStructureIdentifier(
                ['config' => $columnConfiguration],
                $record->getTableName(),
                $column,
                $record->getLocalProperties()
            );
            $flexFormDefinition = $flexFormTools->parseDataStructureByIdentifier($dataStructIdentifier);
            $flexFormDefinition = $flexFormDefinition['sheets'];
        } else {
            $flexFormDefinition = [];
            $flexFormSource = $this->getFlexFormDefinitionSource($record, $columnConfiguration);
            if ($flexFormSource !== '') {
                $flexFormString = $this->resolveFlexFormSource($flexFormSource);
                if ($flexFormString === '') {
                    $this->logger->warning(
                        'The FlexForm was empty',
                        [
                            'tableName' => $record->getTableName(),
                            'identifier' => $record->getIdentifier(),
                            'flexFormSource' => $flexFormSource,
                        ]
                    );

                    return $flexFormDefinition;
                }
                $flexFormDefinition = GeneralUtility::xml2array($flexFormString);
            }
            if (isset($flexFormDefinition['sheets'])) {
                $flexFormDefinition = $flexFormDefinition['sheets'];
            }
        }

        $flexFormDefinition = $this->flattenFlexFormDefinition((array)$flexFormDefinition);
        $flexFormDefinition = $this->filterFlexFormDefinition($flexFormDefinition);
        return $flexFormDefinition;
    }

    /**
     * Simplify flexform definition
     *
     *      'sDEF' => array(
     *          'ROOT' => array(
     *              'TCEforms' => array(
     *                  'sheetTitle' => 'Common'
     *              ),
     *              'type' => 'array',
     *              'el' => array(
     *                  'settings.pid' => array(
     *                      'TCEforms' => array(
     *                          'exclude' => '1',
     *                          'label' => 'test',
     *                          'config' => array(
     *                              'type' => 'group'
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      )
     *
     *      =>
     *
     *      'settings.pid' => array(
     *          'type' => 'group'
     *      )
     *
     * @param array $flexFormDefinition
     * @return array
     */
    protected function flattenFlexFormDefinition(array $flexFormDefinition)
    {
        $flattenedDefinition = [];
        foreach ($flexFormDefinition as $sheetDefinition) {
            foreach ($sheetDefinition as $rootDefinition) {
                if (is_array($rootDefinition) && !empty($rootDefinition['el'])) {
                    foreach ($rootDefinition['el'] as $fieldKey => $fieldDefinition) {
                        $flattenedDefinition = $this->flattenFieldFlexForm(
                            $flattenedDefinition,
                            $fieldDefinition,
                            $fieldKey
                        );
                    }
                }
            }
        }
        return $flattenedDefinition;
    }

    /**
     * Simplify flexform definition for a single field
     *
     *      'key' => array(
     *          'TCEforms' => array(
     *              'exclude' => '1',
     *              'label' => 'test',
     *              'config' => array(
     *                  'type' => 'group'
     *              )
     *          )
     *      )
     *
     *      =>
     *
     *      'key' => array(
     *          'type' => 'group'
     *      )
     *
     * @param array $flattenedDefinition
     * @param array $fieldDefinition
     * @param string $fieldKey
     * @return array
     */
    protected function flattenFieldFlexForm(array $flattenedDefinition, array $fieldDefinition, $fieldKey)
    {
        // default FlexForm for a single field
        if (array_key_exists('TCEforms', $fieldDefinition)) {
            $flattenedDefinition[$fieldKey] = $fieldDefinition['TCEforms']['config'];
        } else {
            // advanced FlexForm for a single field with n subfields
            if (array_key_exists('el', $fieldDefinition)) {
                $fieldDefinition = $fieldDefinition['el'];
                foreach (array_keys($fieldDefinition) as $subKey) {
                    if (array_key_exists('el', $fieldDefinition[$subKey])) {
                        foreach ($fieldDefinition[$subKey]['el'] as $subFieldKey => $subFieldDefinition) {
                            $newFieldKey = $fieldKey . '.[ANY].' . $subKey . '.' . $subFieldKey;
                            $flattenedDefinition = $this->flattenFieldFlexForm(
                                $flattenedDefinition,
                                $subFieldDefinition,
                                $newFieldKey
                            );
                        }
                    }
                }
            }
        }
        return $flattenedDefinition;
    }

    /**
     * @param array $flexFormDefinition
     * @return array
     */
    protected function filterFlexFormDefinition(array $flexFormDefinition)
    {
        foreach ($flexFormDefinition as $key => $config) {
            if (empty($config['type'])
                || !(in_array($config['type'], ['select', 'group', 'inline'])
                     || ($config['type'] === 'input' && !empty($config['wizards'])))
            ) {
                unset($flexFormDefinition[$key]);
            }
        }
        return $flexFormDefinition;
    }

    /**
     * @param array $originalData
     * @param array $flexFormDefinition
     * @return array
     */
    protected function getFlexFormDataByDefinition(array $originalData, array $flexFormDefinition)
    {
        $flexFormData = [];
        $keys = array_keys($flexFormDefinition);
        foreach ($keys as $key) {
            $indexStack = explode('.', $key);
            $flexFormData[$key] = $this->getValueByIndexStack($indexStack, $originalData);
        }
        return $flexFormData;
    }

    /**
     * @param array $indexStack
     * @param array $data
     * @param array $pathStack
     * @return mixed
     */
    protected function getValueByIndexStack(array $indexStack, array $data, array &$pathStack = [])
    {
        $workingData = $data;
        while ($index = array_shift($indexStack)) {
            if ($index === '[ANY]') {
                foreach ($workingData as $subtreeIndex => $subtreeWorkingData) {
                    unset($workingData[$subtreeIndex]);
                    $tmp = $pathStack;
                    array_push($pathStack, $subtreeIndex);
                    $value = $this->getValueByIndexStack($indexStack, $subtreeWorkingData, $pathStack);
                    $workingData[implode('.', $pathStack)] = $value;
                    $pathStack = $tmp;
                }
                return $workingData;
            } else {
                array_push($pathStack, $index);
                if (array_key_exists($index, $workingData)) {
                    $workingData = $workingData[$index];
                } else {
                    return null;
                }
            }
        }
        return $workingData;
    }

    /**
     * Get saved flexform from database
     *
     * @param RecordInterface $record
     * @param string $column
     * @return array
     */
    protected function getLocalFlexFormDataFromRecord(RecordInterface $record, $column)
    {
        /** @var FlexFormService $flexFormService */
        $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);

        $localFlexFormData = [];
        if ($record->hasLocalProperty($column)) {
            $localFlexFormData = $flexFormService->convertFlexFormContentToArray($record->getLocalProperty($column));
        }
        return $localFlexFormData;
    }

    /**
     * Records like Plugins may have related Records defined in FlexForms
     * This method searched for the FlexForm Structure and
     * combines it with the FlexForm data array.
     * Currently only FlexForms using "select" and "group/db"-relations are supported
     *
     * @param RecordInterface $record
     * @param string $column
     * @param array $excludedTableNames
     * @param array $columnConfiguration
     * @return array
     * @throws \Exception
     */
    protected function fetchRelatedRecordsByFlexForm(
        RecordInterface $record,
        $column,
        array $excludedTableNames,
        array $columnConfiguration
    ) {
        $records = [];

        $localFlexFormData = $this->getLocalFlexFormDataFromRecord($record, $column);
        if (empty($localFlexFormData)) {
            return $records;
        }

        $flexFormDefinition = $this->getFlexFormDefinition($record, $column, $columnConfiguration);
        if (empty($flexFormDefinition)) {
            return $records;
        }

        $flexFormData = $this->getFlexFormDataByDefinition($localFlexFormData, $flexFormDefinition);
        if (empty($flexFormData)) {
            return $records;
        }

        foreach ($flexFormDefinition as $key => $config) {
            if (!empty($flexFormData[$key])) {
                if (false === strpos($key, '[ANY]')) {
                    $currentFlexFormData = [$flexFormData[$key]];
                } else {
                    $currentFlexFormData = $flexFormData[$key];
                }
                foreach ($currentFlexFormData as $currentFlexFormDatum) {
                    $newRecords = $this->getRecordsByFlexFormRelation(
                        $record,
                        $column,
                        $excludedTableNames,
                        $config,
                        $currentFlexFormDatum
                    );
                    $records = array_merge($records, $newRecords);
                }
            }
        }
        return $records;
    }

    /**
     * @param RecordInterface $record
     * @param $column
     * @param array $exclTables
     * @param $config
     * @param mixed $flexFormData
     * @return array
     * @throws \Exception
     */
    protected function getRecordsByFlexFormRelation(
        RecordInterface $record,
        $column,
        array $exclTables,
        $config,
        $flexFormData
    ) {
        $records = [];
        $recTable = $record->getTableName();
        $recordId = $record->getIdentifier();
        switch ($config['type']) {
            case 'select':
                $records = $this->fetchRelatedRecordsBySelect($config, $record, $flexFormData, $exclTables, true);
                break;
            case 'inline':
                $records = $this->fetchRelatedRecordsByInline($config, $recTable, $recordId, $exclTables);
                break;
            case 'group':
                $records = $this->fetchRelatedRecordsByGroup($config, $record, $column, $exclTables, $flexFormData);
                break;
            case 'input':
                $records = $this->fetchRelatedRecordsByRte($flexFormData, $exclTables);
                break;
            default:
                $this->logger->emergency(
                    'A weird error occurred. An unsupported FlexForm type sneaked through the FlexForm filter',
                    [
                        'sheetConfiguration' => $config,
                        'column' => $column,
                        'tableName' => $recTable,
                        'identifier' => $recordId,
                    ]
                );
        }
        return $records;
    }

    /**
     * @param array $columnConfiguration
     * @param RecordInterface $record
     * @param array $excludedTableNames
     * @param string $propertyName
     * @param array $overrideIdentifiers
     * @return RecordInterface[]
     */
    protected function fetchRelatedRecordsByGroupTypeDb(
        array $columnConfiguration,
        RecordInterface $record,
        array $excludedTableNames,
        $propertyName,
        array $overrideIdentifiers = []
    ) {
        /** @var RecordInterface[] $records */
        $records = [];
        $tableName = '';
        if (!empty($columnConfiguration['foreign_table'])) {
            $tableName = $columnConfiguration['foreign_table'];
        } elseif (!empty($columnConfiguration['allowed'])) {
            $tableName = $columnConfiguration['allowed'];
        }
        if (strpos($tableName, ',') !== false) {
            $tableNames = explode(',', $tableName);
            if (!empty($overrideIdentifiers)) {
                $identifierArray = $overrideIdentifiers;
            } else {
                $identifierArray = array_filter(
                    GeneralUtility::trimExplode(
                        ',',
                        $record->getLocalProperty($propertyName)
                    )
                );
            }
            if (!empty($identifierArray)) {
                $identifierToTableMap = [];
                foreach ($tableNames as $tableName) {
                    if (in_array($tableName, $excludedTableNames)) {
                        continue;
                    }
                    foreach ($identifierArray as $identifier) {
                        if (strpos($identifier, $tableName) !== false) {
                            $id = substr($identifier, strlen($tableName) + 1);
                            $identifierToTableMap[$tableName][] = $id;
                        }
                    }
                }
                foreach ($identifierToTableMap as $tableName => $identifiers) {
                    $previousTableName = $this->replaceTableName($tableName);
                    if ($columnConfiguration['MM']) {
                        $this->logger->alert(
                            'Missing implementation: GROUP MM',
                            [
                                'table' => $record->getTableName(),
                                'property' => $propertyName,
                                'columnConfiguration' => $columnConfiguration,
                            ]
                        );
                    }
                    foreach ($identifiers as $identifier) {
                        $records = array_merge(
                            $records,
                            $this->convertPropertyArraysToRecords(
                                $this->findPropertiesByProperty(
                                    $this->localDatabase,
                                    'uid',
                                    $identifier
                                ),
                                $this->findPropertiesByProperty($this->foreignDatabase, 'uid', $identifier)
                            )
                        );
                    }
                    $this->tableName = $previousTableName;
                }
            }
        } else {
            if (in_array($tableName, $excludedTableNames)) {
                return $records;
            }
            if ($columnConfiguration['MM']) {
                // skip if this record is not the owning side of the relation
                if (!empty($columnConfiguration['MM_oppositeUsage'])) {
                    return $records;
                }
                if (!empty($columnConfiguration['MM_match_fields'])
                    || !empty($columnConfiguration['MM_insert_fields'])
                    || !empty($columnConfiguration['MM_table_where'])
                    || !empty($columnConfiguration['MM_hasUidField'])
                ) {
                    $this->logger->error(
                        'Group MM relations with MM_match_fields,'
                        . ' MM_insert_fields, MM_table_where or MM_hasUidField are not supported',
                        [
                            'tableName' => $tableName,
                            'propertyName' => $propertyName,
                            'columnConfiguration' => $columnConfiguration,
                        ]
                    );
                }
                $previousTable = $this->replaceTableName($columnConfiguration['MM']);
                $records = $this->convertPropertyArraysToRecords(
                    $this->findPropertiesByProperty(
                        $this->localDatabase,
                        $this->getLocalField($columnConfiguration),
                        $record->getIdentifier(),
                        '',
                        '',
                        '',
                        '',
                        'uid_local,uid_foreign'
                    ),
                    $this->findPropertiesByProperty(
                        $this->foreignDatabase,
                        $this->getLocalField($columnConfiguration),
                        $record->getIdentifier(),
                        '',
                        '',
                        '',
                        '',
                        'uid_local,uid_foreign'
                    )
                );
                $this->tableName = $previousTable;
                /** @var RecordInterface $relatedRecord */
                foreach ($records as $relatedRecord) {
                    if ($relatedRecord->hasLocalProperty('tablenames')) {
                        $originalTableName = $relatedRecord->hasLocalProperty('tablenames');
                    } else {
                        $originalTableName = $tableName;
                    }
                    $localUid = $relatedRecord->getLocalProperty($this->getForeignField($columnConfiguration));
                    $foreignUid = $relatedRecord->getForeignProperty($this->getForeignField($columnConfiguration));

                    if ($localUid > 0 && $foreignUid > 0 && $localUid !== $foreignUid) {
                        $this->logger->alert(
                            'Detected different UIDs in fetchRelatedRecordsByGroup',
                            [
                                'columnConfiguration' => $columnConfiguration,
                                'recordTableName' => $this->tableName,
                                'relatedRecordIdentifier' => $relatedRecord->getIdentifier(),
                            ]
                        );
                        continue;
                    }
                    if (!in_array($originalTableName, $excludedTableNames)) {
                        $originalRecord = $this->findByIdentifierInOtherTable($localUid, $originalTableName);
                        if ($originalRecord !== null) {
                            $relatedRecord->addRelatedRecord($originalRecord);
                        }
                    }
                }
            } else {
                $previousTableName = $this->replaceTableName($tableName);
                if (!empty($overrideIdentifiers)) {
                    $identifiers = $overrideIdentifiers;
                } else {
                    $identifiers = array_filter(
                        GeneralUtility::trimExplode(
                            ',',
                            $record->getMergedProperty($propertyName)
                        )
                    );
                }
                foreach ($identifiers as $identifier) {
                    $records = array_merge(
                        $records,
                        $this->convertPropertyArraysToRecords(
                            $this->findPropertiesByProperty(
                                $this->localDatabase,
                                'uid',
                                $identifier
                            ),
                            $this->findPropertiesByProperty($this->foreignDatabase, 'uid', $identifier)
                        )
                    );
                }
                $this->tableName = $previousTableName;
            }
        }
        return $records;
    }

    /**
     * type=group is widely spread among old Extensions and Core.
     * This method fetches records related by group.
     *
     * @param array $columnConfiguration
     * @param RecordInterface $record
     * @param string $propertyName
     * @param array $excludedTableNames
     * @param string $flexFormData
     * @return array
     * @throws \Exception
     */
    protected function fetchRelatedRecordsByGroup(
        array $columnConfiguration,
        RecordInterface $record,
        $propertyName,
        array $excludedTableNames,
        $flexFormData = ''
    ) {
        $records = [];
        switch ($columnConfiguration['internal_type']) {
            case 'db':
                $records = $this->fetchRelatedRecordsByGroupTypeDb(
                    $columnConfiguration,
                    $record,
                    $excludedTableNames,
                    $propertyName,
                    GeneralUtility::trimExplode(',', $flexFormData, true)
                );
                break;
            case 'file_reference':
            case 'file':
                $fileAndPathNames = $this->getFileAndPathNames(
                    $columnConfiguration,
                    $record,
                    $propertyName,
                    $flexFormData
                );
                foreach ($fileAndPathNames as $fileAndPathName) {
                    $previousTableName = $this->replaceTableName('sys_file');
                    $previousIdFieldName = $this->identifierFieldName;
                    $this->identifierFieldName = 'identifier';
                    $record = $this->findByIdentifier($fileAndPathName);
                    $this->identifierFieldName = $previousIdFieldName;
                    if ($record instanceof RecordInterface) {
                        $recordIdentifier = $record->getIdentifier();

                        // special case: the record exists only in the local database and the same uid
                        // is existent in the foreign table, but not with the given identifier
                        // Solution: Re-fetch the record by its UID, so we ensure we can overwrite the foreign record,
                        // given the relation is broken
                        if (RecordInterface::RECORD_STATE_ADDED === $record->getState()) {
                            $this->recordFactory->forgetCachedRecord($this->getTableName(), $recordIdentifier);
                            $record = $this->findByIdentifier($recordIdentifier);
                            if (RecordInterface::RECORD_STATE_ADDED !== $record->getState()) {
                                $this->logger->notice(
                                    'Detected broken record relation between local and foreign. '
                                    . 'The foreign\'s identifier differs from the local, but the uid is the same',
                                    ['uid' => $recordIdentifier, 'identifier' => $fileAndPathName]
                                );
                            }
                        }
                        $records[$recordIdentifier] = $record;
                    }
                    $this->tableName = $previousTableName;
                }
                break;
            default:
                $this->logger->alert(
                    'Missing implementation: GROUP TYPE',
                    [
                        'table' => $record->getTableName(),
                        'property' => $propertyName,
                        'columnConfiguration' => $columnConfiguration,
                    ]
                );
        }
        return $records;
    }

    /**
     * Get file and path (and index it)
     *
     * @param array $columnConfiguration
     * @param RecordInterface $record
     * @param string $propertyName
     * @param string $flexFormData
     * @return array
     */
    protected function getFileAndPathNames(
        array $columnConfiguration,
        RecordInterface $record,
        $propertyName,
        $flexFormData
    ) {
        $prefix = '';
        if (!empty($columnConfiguration['uploadfolder'])) {
            $prefix = FileUtility::getCleanFolder($columnConfiguration['uploadfolder']);
        }
        if (empty($flexFormData)) {
            $fileNames = GeneralUtility::trimExplode(',', $record->getLocalProperty($propertyName), true);
        } else {
            $fileNames = GeneralUtility::trimExplode(',', $flexFormData, true);
        }
        foreach ($fileNames as $key => $filename) {
            // Force indexing of the record
            $fileNames[$key] = GeneralUtility::makeInstance(ResourceFactory::class)
                                             ->getFileObjectFromCombinedIdentifier($prefix . $filename)
                                             ->getIdentifier();
        }
        return $fileNames;
    }

    /**
     * Fetches records by select relations. supports MM tables
     *
     * @param array $columnConfiguration
     * @param RecordInterface $record
     * @param string $propertyName
     * @param array $excludedTableNames
     * @param bool $overrideIdByRecord
     * @return array
     */
    protected function fetchRelatedRecordsBySelect(
        array $columnConfiguration,
        RecordInterface $record,
        $propertyName,
        array $excludedTableNames,
        $overrideIdByRecord = false
    ) {
        $tableName = $columnConfiguration['foreign_table'];
        if (in_array($tableName, $excludedTableNames)) {
            return [];
        }
        $previousTableName = $this->replaceTableName($tableName);
        $records = [];

        if ($overrideIdByRecord) {
            $recordIdentifier = $propertyName;
        } else {
            $recordIdentifier = $record->getMergedProperty($propertyName);
        }

        if ($recordIdentifier !== null && trim($recordIdentifier) !== '' && (int)$recordIdentifier > 0) {
            // if the relation is an MM type, then select all identifiers from the MM table
            if (!empty($columnConfiguration['MM'])) {
                $records = $this->fetchRelatedRecordsBySelectMm($columnConfiguration, $record, $excludedTableNames);
            } else {
                $whereClause = '';
                if (!empty($columnConfiguration['foreign_table_where'])) {
                    /** @var ReplaceMarkersService $replaceMarkers */
                    $replaceMarkers = GeneralUtility::makeInstance(
                        ReplaceMarkersService::class,
                        $this->localDatabase,
                        $this->foreignDatabase
                    );
                    $whereClause = $replaceMarkers->replaceMarkers(
                        $record,
                        $columnConfiguration['foreign_table_where']
                    );
                }

                $uidArray = [];

                if (MathUtility::canBeInterpretedAsInteger($recordIdentifier)) {
                    $uidArray[] = $recordIdentifier;
                } elseif (is_string($recordIdentifier) && strpos($recordIdentifier, ',')) {
                    $uidArray = GeneralUtility::trimExplode(',', $recordIdentifier);
                } elseif (is_array($recordIdentifier)) {
                    $uidArray = $recordIdentifier;
                }
                foreach ($uidArray as $uid) {
                    $records = array_merge(
                        $records,
                        $this->convertPropertyArraysToRecords(
                            $this->findPropertiesByProperty(
                                $this->localDatabase,
                                'uid',
                                $uid,
                                $whereClause
                            ),
                            $this->findPropertiesByProperty($this->foreignDatabase, 'uid', $uid, $whereClause)
                        )
                    );
                }
            }
        }
        $this->tableName = $previousTableName;
        return $records;
    }

    /**
     * @param array $columnConfiguration
     * @param RecordInterface $record
     * @param array $excludedTableNames
     * @return array
     */
    protected function fetchRelatedRecordsBySelectMm(
        array $columnConfiguration,
        RecordInterface $record,
        array $excludedTableNames
    ) {
        $previousTableName = $this->replaceTableName($columnConfiguration['MM']);

        // log not supported TCA function
        if (!empty($columnConfiguration['MM_table_where'])) {
            $this->logger->warning(
                'MM_table_where of select records is not implemented. Please contact the extension authors at in2code',
                [
                    'columnConfiguration' => $columnConfiguration,
                ]
            );
        }

        // build additional where clause
        $additionalWhereArray = [];
        if (!empty($columnConfiguration['MM_match_fields'])) {
            $foreignMatchFields = [];
            foreach ($columnConfiguration['MM_match_fields'] as $matchField => $matchValue) {
                $foreignMatchFields[] = $matchField . ' LIKE "' . $matchValue . '"';
            }
            $additionalWhereArray = array_merge($additionalWhereArray, $foreignMatchFields);
        }
        $additionalWhere = implode(' AND ', $additionalWhereArray);
        if (strlen($additionalWhere) > 0) {
            $additionalWhere = ' AND ' . $additionalWhere;
        }
        $records = $this->convertPropertyArraysToRecords(
            $this->findPropertiesByProperty(
                $this->localDatabase,
                $this->getLocalField($columnConfiguration),
                $record->getIdentifier(),
                $additionalWhere,
                '',
                '',
                '',
                'uid_local,uid_foreign'
            ),
            $this->findPropertiesByProperty(
                $this->foreignDatabase,
                $this->getLocalField($columnConfiguration),
                $record->getIdentifier(),
                $additionalWhere,
                '',
                '',
                '',
                'uid_local,uid_foreign'
            )
        );

        $foreignField = $this->getForeignField($columnConfiguration);

        /** @var RecordInterface $relationRecord */
        foreach ($records as $relationRecord) {
            $originalTableName = $columnConfiguration['foreign_table'];
            if (!in_array($originalTableName, $excludedTableNames)) {
                $identifier = $relationRecord->getMergedProperty($foreignField);
                $originalRecord = $this->findByIdentifierInOtherTable($identifier, $originalTableName);
                if ($originalRecord !== null) {
                    $relationRecord->addRelatedRecord($originalRecord);
                }
            }
        }

        $this->tableName = $previousTableName;
        return $records;
    }

    /**
     * Fetches inline related Records like FAL images. Often used in FAL
     * or custom Extensions. Lacks support for MM tables
     *
     * @param array $columnConfiguration
     * @param string $recordTableName
     * @param int $recordIdentifier
     * @param array $excludedTableNames
     * @return array
     * @throws \Exception
     */
    protected function fetchRelatedRecordsByInline(
        array $columnConfiguration,
        $recordTableName,
        $recordIdentifier,
        array $excludedTableNames
    ) {
        $tableName = $columnConfiguration['foreign_table'];
        if (in_array($tableName, $excludedTableNames)) {
            return [];
        }
        $previousTableName = $this->replaceTableName($tableName);

        $where = [];

        if (!empty($columnConfiguration['MM'])) {
            $records = $this->fetchRelatedRecordsByInlineMm(
                $columnConfiguration,
                $recordTableName,
                $recordIdentifier,
                $excludedTableNames
            );
            $this->tableName = $previousTableName;
            return $records;
        }

        if (!empty($columnConfiguration['foreign_table_field'])) {
            $where[] = $columnConfiguration['foreign_table_field'] . ' LIKE "' . $recordTableName . '"';
        }

        if (!empty($columnConfiguration['foreign_match_fields'])
            && is_array($columnConfiguration['foreign_match_fields'])
        ) {
            foreach ($columnConfiguration['foreign_match_fields'] as $fieldName => $fieldValue) {
                $where[] = $fieldName . ' LIKE "' . $fieldValue . '"';
            }
        }

        $whereClause = '';
        if (!empty($where)) {
            $whereClause = ' AND ' . implode(' AND ', $where);
        }

        $foreignField = $columnConfiguration['foreign_field'];

        $records = $this->convertPropertyArraysToRecords(
            $this->findPropertiesByProperty(
                $this->localDatabase,
                $foreignField,
                $recordIdentifier,
                $whereClause
            ),
            $this->findPropertiesByProperty($this->foreignDatabase, $foreignField, $recordIdentifier, $whereClause)
        );

        $this->tableName = $previousTableName;
        return $records;
    }

    /**
     * @param array $columnConfiguration
     * @param $recordTableName
     * @param $recordIdentifier
     * @param array $excludedTableNames
     * @return array
     */
    protected function fetchRelatedRecordsByInlineMm(
        array $columnConfiguration,
        $recordTableName,
        $recordIdentifier,
        array $excludedTableNames
    ) {
        if (!empty($columnConfiguration['foreign_field'])
            || !empty($columnConfiguration['foreign_selector'])
            || !empty($columnConfiguration['foreign_record_defaults'])
            || !empty($columnConfiguration['filter'])
            || !empty($columnConfiguration['foreign_types'])
            || !empty($columnConfiguration['foreign_types'])
            || !empty($columnConfiguration['foreign_table_field'])
        ) {
            $this->logger->error(
                'Inline MM relations with foreign_field, foreign_types, symmetric_field, filter, '
                . 'foreign_table_field, foreign_record_defaults or foreign_selector are not supported',
                [
                    'columnConfiguration' => $columnConfiguration,
                    'recordTableName' => $recordTableName,
                    'recordIdentifier' => $recordIdentifier,
                ]
            );
        }
        $previousTable = $this->replaceTableName($columnConfiguration['MM']);
        $relationRecords = $this->convertPropertyArraysToRecords(
            $this->findPropertiesByProperty(
                $this->localDatabase,
                'uid_local',
                $recordIdentifier,
                '',
                '',
                '',
                '',
                'uid_local,uid_foreign'
            ),
            $this->findPropertiesByProperty(
                $this->foreignDatabase,
                'uid_local',
                $recordIdentifier,
                '',
                '',
                '',
                '',
                'uid_local,uid_foreign'
            )
        );
        $this->fetchOriginalRecordsForInlineRecord(
            $relationRecords,
            $columnConfiguration,
            $recordTableName,
            $recordIdentifier,
            $excludedTableNames
        );
        $this->tableName = $previousTable;
        return $relationRecords;
    }

    /**
     * @param RecordInterface[] $relationRecords
     * @param array $columnConfiguration
     * @param string $recordTableName
     * @param string $recordIdentifier
     * @param array $excludedTableNames
     * @return array
     */
    protected function fetchOriginalRecordsForInlineRecord(
        array $relationRecords,
        array $columnConfiguration,
        $recordTableName,
        $recordIdentifier,
        array $excludedTableNames
    ) {
        /** @var RecordInterface $mmRecord */
        foreach ($relationRecords as $mmRecord) {
            $localUid = $mmRecord->getLocalProperty('uid_foreign');
            $foreignUid = $mmRecord->getForeignProperty('uid_foreign');

            if ($localUid > 0 && $foreignUid > 0 && $localUid !== $foreignUid) {
                $this->logger->alert(
                    'Detected different UIDs in fetchRelatedRecordsByInline',
                    [
                        'columnConfiguration' => $columnConfiguration,
                        'recordTableName' => $recordTableName,
                        'recordIdentifier' => $recordIdentifier,
                    ]
                );
                continue;
            }

            $originalTableName = $columnConfiguration['foreign_table'];
            if (!in_array($originalTableName, $excludedTableNames)) {
                $originalRecord = $this->findByIdentifierInOtherTable($localUid, $columnConfiguration['foreign_table']);
                if ($originalRecord !== null) {
                    $mmRecord->addRelatedRecord($originalRecord);
                }
            }
        }
        return $relationRecords;
    }

    /**
     * TemplateMethod like function the find Records
     * in the given Table.
     *
     * @param int $identifier
     * @param string $tableName
     * @return RecordInterface
     */
    protected function findByIdentifierInOtherTable($identifier, $tableName)
    {
        $previousIdFieldName = $this->identifierFieldName;
        $this->identifierFieldName = 'uid';
        $previousTableName = $this->replaceTableName($tableName);
        $relatedRecord = $this->findByIdentifier($identifier);
        $this->setTableName($previousTableName);
        $this->identifierFieldName = $previousIdFieldName;
        return $relatedRecord;
    }

    /**
     * Check if this record should be ignored
     *
     * @param array $localProperties
     * @param array $foreignProperties
     * @return bool
     */
    protected function isIgnoredRecord(array $localProperties, array $foreignProperties)
    {
        if ($this->isDeletedAndUnchangedRecord($localProperties, $foreignProperties)
            || $this->isRemovedAndDeletedRecord($localProperties, $foreignProperties)
            || $this->shouldIgnoreRecord($localProperties, $foreignProperties)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Check if the record is removed on one side (never existed) and was deleted on the other (it got deleted)
     *
     * @param array $localProps
     * @param array $foreignProps
     * @return bool
     */
    protected function isRemovedAndDeletedRecord(array $localProps, array $foreignProps)
    {
        return (empty($localProps) && isset($foreignProps['deleted']) && 1 === (int)$foreignProps['deleted'])
               || (empty($foreignProps) && isset($localProps['deleted']) && 1 === (int)$localProps['deleted']);
    }

    /**
     * Check if this record is deleted on local and foreign
     * site and both are completely identical (don't show)
     *
     * @param array $localProperties
     * @param array $foreignProperties
     * @return bool
     */
    protected function isDeletedAndUnchangedRecord(array $localProperties, array $foreignProperties)
    {
        return $localProperties['deleted'] === '1' && !count(array_diff($localProperties, $foreignProperties));
    }

    /**
     * Publishes the given Record and all related Records
     * where the related Record's tableName is not excluded
     *
     * @param RecordInterface $record
     * @param array $excludedTables
     * @param array $alreadyVisited
     * @return void
     * @throws \Exception
     */
    public function publishRecordRecursive(
        RecordInterface $record,
        array $excludedTables = ['pages'],
        array $alreadyVisited = []
    ) {
        try {
            // Dispatch Anomaly
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'publishRecordRecursiveBegin',
                [$record, $this]
            );

            $this->publishRecordRecursiveInternal($record, $excludedTables, $alreadyVisited);

            // Dispatch Anomaly
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'publishRecordRecursiveEnd',
                [$record, $this]
            );
        } catch (\Exception $exception) {
            $this->logger->critical(
                'Publishing single record failed',
                [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ]
            );
            throw $exception;
        }
    }

    /**
     * @param RecordInterface $record
     * @param array $excludedTables
     * @param array $alreadyVisited
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    protected function publishRecordRecursiveInternal(
        RecordInterface $record,
        array $excludedTables,
        array $alreadyVisited
    ) {
        $tableName = $record->getTableName();

        if (!empty($alreadyVisited[$tableName])) {
            if (in_array($record->getIdentifier(), $alreadyVisited[$tableName])) {
                return;
            }
        }
        $alreadyVisited[$tableName][] = $record->getIdentifier();

        if (!$this->shouldSkipRecord($record, $tableName)) {
            // Dispatch Anomaly
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'publishRecordRecursiveBeforePublishing',
                [$tableName, $record, $this]
            );

            /*
             * For Records shown as moved:
             * Since moved pages only get published explicitly, they will
             * have the state "changed" instead of "moved".
             * Because of this, we don't need to take care about that state
             */

            if ($record->hasAdditionalProperty('recordDatabaseState')) {
                $state = $record->getAdditionalProperty('recordDatabaseState');
            } else {
                $state = $record->getState();
            }

            if (true === $record->getAdditionalProperty('isPrimaryIndex')) {
                $this->logger->notice(
                    'Removing duplicate index from remote',
                    [
                        'tableName' => $record->getTableName(),
                        'local_uid' => $record->getLocalProperty('uid'),
                        'foreign_uid' => $record->getForeignProperty('uid'),
                    ]
                );
                // remove duplicate remote index
                $previousTableName = $this->replaceTableName($record->getTableName());
                $this->deleteRecord($this->foreignDatabase, $record->getForeignProperty('uid'));
                $this->setTableName($previousTableName);
            }

            if ($state === RecordInterface::RECORD_STATE_CHANGED || $state === RecordInterface::RECORD_STATE_MOVED) {
                $this->updateForeignRecord($record);
            } elseif ($state === RecordInterface::RECORD_STATE_ADDED) {
                $this->addForeignRecord($record);
            } elseif ($state === RecordInterface::RECORD_STATE_DELETED) {
                if ($record->localRecordExists()) {
                    $this->updateForeignRecord($record);
                } elseif ($record->foreignRecordExists()) {
                    $this->deleteForeignRecord($record);
                }
            }

            // Dispatch Anomaly
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'publishRecordRecursiveAfterPublishing',
                [$tableName, $record, $this]
            );

            // set the records state to published/unchanged to prevent
            // a second INSERT or UPDATE (superfluous queries)
            $record->setState(RecordInterface::RECORD_STATE_UNCHANGED);
        }

        // publish all related records
        $this->publishRelatedRecordsRecursive($record, $excludedTables, $alreadyVisited);
    }

    /**
     * Publishes all related Records of the given record if
     * their tableName is not included in $excludedTables
     *
     * @param RecordInterface $record
     * @param array $excludedTables
     * @param array $alreadyVisited
     * @return void
     */
    protected function publishRelatedRecordsRecursive(
        RecordInterface $record,
        array $excludedTables,
        array $alreadyVisited = []
    ) {
        foreach ($record->getRelatedRecords() as $tableName => $relatedRecords) {
            if (!in_array($tableName, $excludedTables) && is_array($relatedRecords)) {
                /** @var RecordInterface $relatedRecord */
                foreach ($relatedRecords as $relatedRecord) {
                    $this->publishRecordRecursiveInternal($relatedRecord, $excludedTables, $alreadyVisited);
                }
            }
        }
    }

    /**
     * converts properties from local and foreign
     * to a record using the factory
     *
     * @param array $localProperties
     * @param array $foreignProperties
     * @return RecordInterface
     */
    protected function convertToRecord(array $localProperties, array $foreignProperties)
    {
        return $this->recordFactory->makeInstance($this, $localProperties, $foreignProperties);
    }

    /**
     * Publishing Method: Executes an UPDATE query on the
     * foreign Database with all record properties
     *
     * @param RecordInterface $record
     * @return void
     */
    protected function updateForeignRecord(RecordInterface $record)
    {
        $previousTableName = $this->replaceTableName($record->getTableName());
        $this->updateRecord($this->foreignDatabase, $record->getIdentifier(), $record->getLocalProperties());
        $this->setTableName($previousTableName);
    }

    /**
     * Publishing Method: Executes an INSERT query on the
     * foreign database with all record properties
     *
     * @param RecordInterface $record
     * @return void
     */
    protected function addForeignRecord(RecordInterface $record)
    {
        $previousTableName = $this->replaceTableName($record->getTableName());
        $this->addRecord($this->foreignDatabase, $record->getLocalProperties());
        $this->setTableName($previousTableName);
    }

    /**
     * Publishing Method: Removes a row from the foreign database
     * This can not be undone and the row will be removed forever
     *
     * Since this action is highly destructive, it
     * must be enabled in the Configuration
     *
     * @param RecordInterface $record
     * @return void
     */
    protected function deleteForeignRecord(RecordInterface $record)
    {
        $this->logger->notice(
            'Deleting foreign record',
            [
                'localProperties' => $record->getLocalProperties(),
                'foreignProperties' => $record->getForeignProperties(),
                'tableName' => $record->getTableName(),
                'identifier' => $record->getIdentifier(),
            ]
        );
        $previousTableName = $this->replaceTableName($record->getTableName());
        $this->deleteRecord($this->foreignDatabase, $record->getIdentifier());
        $this->setTableName($previousTableName);
    }

    /**
     * Get local field for mm tables (and switch name if "MM_opposite_field" is set)
     *
     * @param array $columnConfiguration
     * @return string
     */
    protected function getLocalField(array $columnConfiguration)
    {
        $localField = 'uid_local';
        if (!empty($columnConfiguration['MM_opposite_field'])) {
            $localField = 'uid_foreign';
        }
        return $localField;
    }

    /**
     * Get foreign field for mm tables (and switch name if "MM_opposite_field" is set)
     *
     * @param array $columnConfiguration
     * @return string
     */
    protected function getForeignField(array $columnConfiguration)
    {
        $localField = 'uid_foreign';
        if (!empty($columnConfiguration['MM_opposite_field'])) {
            $localField = 'uid_local';
        }
        return $localField;
    }

    /**
     * Disable Page Recursion
     *
     * @return void
     */
    public function disablePageRecursion()
    {
        $this->recordFactory->disablePageRecursion();
    }

    /**
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     * @param string $identifier
     * @return bool
     */
    protected function shouldSkipFindByIdentifier($identifier)
    {
        return $this->should('shouldSkipFindByIdentifier', ['identifier' => $identifier]);
    }

    /**
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     * @param string $propertyName
     * @param mixed $propertyValue
     * @return bool
     */
    protected function shouldSkipFindByProperty($propertyName, $propertyValue)
    {
        $arguments = ['propertyName' => $propertyName, 'propertyValue' => $propertyValue];
        return $this->should('shouldSkipFindByProperty', $arguments);
    }

    /**
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     * @param RecordInterface $record
     * @return bool
     */
    protected function shouldSkipSearchingForRelatedRecords(RecordInterface $record)
    {
        return $this->should('shouldSkipSearchingForRelatedRecords', ['record' => $record]);
    }

    /**
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     * @param RecordInterface $record
     * @param string $propertyName
     * @param array $columnConfiguration
     * @return bool
     */
    protected function shouldSkipSearchingForRelatedRecordsByProperty(
        RecordInterface $record,
        $propertyName,
        array $columnConfiguration
    ) {
        $arguments = [
            'record' => $record,
            'propertyName' => $propertyName,
            'columnConfiguration' => $columnConfiguration,
        ];
        return $this->should('shouldSkipSearchingForRelatedRecordsByProperty', $arguments);
    }

    /**
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     * @param RecordInterface $record
     * @return bool
     */
    protected function shouldSkipEnrichingPageRecord(RecordInterface $record)
    {
        return $this->should('shouldSkipEnrichingPageRecord', ['record' => $record]);
    }

    /**
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     * @param RecordInterface $record
     * @param string $tableName
     * @return bool
     */
    protected function shouldSkipSearchingForRelatedRecordByTable(RecordInterface $record, $tableName)
    {
        return $this->should(
            'shouldSkipSearchingForRelatedRecordByTable',
            ['record' => $record, 'tableName' => $tableName]
        );
    }

    /**
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     * @param RecordInterface $record
     * @param string $tableName
     * @return bool
     */
    protected function shouldSkipRecord(RecordInterface $record, $tableName)
    {
        return $this->should('shouldSkipRecord', ['record' => $record, 'tableName' => $tableName]);
    }

    /**
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     * @param array $localProperties
     * @param array $foreignProperties
     * @return bool
     */
    protected function shouldIgnoreRecord(array $localProperties, array $foreignProperties)
    {
        return $this->should(
            'shouldIgnoreRecord',
            [
                'localProperties' => $localProperties,
                'foreignProperties' => $foreignProperties,
                'tableName' => $this->tableName,
            ]
        );
    }

    /**
     * Slot method signature:
     *  public function slotMethod($votes, CommonRepository $commonRepository, array $additionalArguments)
     *
     * Slot method body:
     *  Either "$votes['yes']++;" or "$votes['no']++;" based on your decision
     *
     * Slot method return:
     *  return array($votes, $commonRepository, $additionalArguments);
     *
     * @param string $signal Name of the registered signal to dispatch
     * @param array $arguments additional arguments to be passed to the slot
     * @return bool If no vote was received false will be returned
     */
    protected function should($signal, array $arguments)
    {
        $signalArguments = $this->signalSlotDispatcher->dispatch(
            __CLASS__,
            $signal,
            [['yes' => 0, 'no' => 0], $this, $arguments]
        );
        return $signalArguments[0]['yes'] > $signalArguments[0]['no'];
    }

    /**
     * @param string $tableName
     * @return CommonRepository
     */
    public static function getDefaultInstance($tableName = 'pages')
    {
        return GeneralUtility::makeInstance(
            CommonRepository::class,
            DatabaseUtility::buildLocalDatabaseConnection(),
            DatabaseUtility::buildForeignDatabaseConnection(),
            $tableName
        );
    }
}
