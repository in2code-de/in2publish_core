<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Repository;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de
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

use Exception;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\NullRecord;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Service\ReplaceMarkersService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use In2code\In2publishCore\Utility\FileUtility;
use Throwable;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowLoopException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowRootException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidPointerFieldValueException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;
use function array_diff;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_push;
use function array_shift;
use function array_unique;
use function count;
use function explode;
use function gettype;
use function htmlspecialchars_decode;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function key;
use function parse_str;
use function parse_url;
use function preg_match_all;
use function reset;
use function sprintf;
use function strlen;
use function strpos;
use function substr;
use function trigger_error;
use function trim;
use const E_USER_DEPRECATED;

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
    const REGEX_T3URN = '~(?P<URN>t3\://(?:file|page)\?uid=\d+)~';
    const SIGNAL_RELATION_RESOLVER_RTE = 'relationResolverRTE';
    const DEPRECATION_METHOD_FPBPATN = 'CommonRepository::findPropertiesByPropertyAndTablename is deprecated and will be removed in in2publish_core version 10. Use BaseRepository::findPropertiesByProperty instead';

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
     * @var Connection
     */
    protected $localDatabase = null;

    /**
     * @var Connection
     */
    protected $foreignDatabase = null;

    /**
     * Cache for skipped records
     *
     * @var array
     */
    protected $skipRecords = [];

    /**
     * @var array
     */
    protected $visitedRecords = [];

    /**
     * @param Connection $localDatabase
     * @param Connection $foreignDatabase
     * @param string|null $tableName
     * @param string $identifierFieldName
     */
    public function __construct(
        Connection $localDatabase,
        Connection $foreignDatabase,
        string $tableName = null,
        string $identifierFieldName = null
    ) {
        if (null !== $tableName) {
            trigger_error(sprintf(self::DEPRECATION_PARAMETER, 'tableName', __METHOD__), E_USER_DEPRECATED);
        }
        if (null !== $identifierFieldName) {
            trigger_error(sprintf(self::DEPRECATION_PARAMETER, 'identifierFieldName', __METHOD__), E_USER_DEPRECATED);
        }
        parent::__construct();
        $this->recordFactory = GeneralUtility::makeInstance(RecordFactory::class);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $this->taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);
        $this->identifierFieldName = $identifierFieldName ?: $this->identifierFieldName;
        $this->localDatabase = $localDatabase;
        $this->foreignDatabase = $foreignDatabase;
        if ($foreignDatabase === null || !$foreignDatabase->isConnected()) {
            $this->foreignDatabase = $localDatabase;
        }
        if (null !== $tableName) {
            $this->setTableName($tableName);
        }
    }

    /**
     * Find and create a Record where the Records identifier equals $identifier
     * Returns exactly one Record.
     *
     * @param int $identifier
     * @param string|null $tableName
     * @param string $idFieldName
     *
     * @return RecordInterface|null
     */
    public function findByIdentifier($identifier, string $tableName = null, $idFieldName = 'uid')
    {
        // TODO: Remove any `identifierFieldName` related stuff from this method with in2publish_core version 10.
        //  It is only required to maintain the function of the deprecated getter of this property.
        $previousIdFieldName = $this->identifierFieldName;
        $this->identifierFieldName = $idFieldName;

        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        if ($this->shouldSkipFindByIdentifier($identifier, $tableName)) {
            return GeneralUtility::makeInstance(NullRecord::class, $tableName);
        }
        $local = $this->findPropertiesByProperty(
            $this->localDatabase,
            $idFieldName,
            $identifier,
            '',
            '',
            '',
            '',
            'uid',
            $tableName
        );
        $local = empty($local) ? [] : reset($local);
        $foreign = $this->findPropertiesByProperty(
            $this->foreignDatabase,
            $idFieldName,
            $identifier,
            '',
            '',
            '',
            '',
            'uid',
            $tableName
        );
        $foreign = empty($foreign) ? [] : reset($foreign);
        $records = $this->recordFactory->makeInstance($this, $local, $foreign, [], $tableName, $idFieldName);
        $this->identifierFieldName = $previousIdFieldName;
        return $records;
    }

    /**
     * Finds and creates none or more Records in the current table name
     * where the propertyName (e.g. pid or tstamp) matches the given value.
     *
     * @param string $propertyName
     * @param mixed $propertyValue
     * @param string|null $tableName
     *
     * @return RecordInterface[]
     */
    public function findByProperty($propertyName, $propertyValue, string $tableName = null): array
    {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        if ($this->shouldSkipFindByProperty($propertyName, $propertyValue)) {
            return [];
        }
        if ($propertyName === 'uid'
            && $record = $this->recordFactory->getCachedRecord($tableName, $propertyValue)
        ) {
            return [$record];
        }
        $localProperties = $this->findPropertiesByProperty(
            $this->localDatabase,
            $propertyName,
            $propertyValue,
            '',
            '',
            '',
            '',
            'uid',
            $tableName
        );
        $foreignProperties = $this->findPropertiesByProperty(
            $this->foreignDatabase,
            $propertyName,
            $propertyValue,
            '',
            '',
            '',
            '',
            'uid',
            $tableName
        );
        return $this->convertPropertyArraysToRecords($localProperties, $foreignProperties, $tableName);
    }

    /**
     * Finds and creates none or more Records in the current table name
     * where the properties are matching.
     *
     * @param array $properties
     * @param bool $simulateRoot Simulate an existent root record to prevent filePostProcessing
     *  in the RecordFactory for each single Record
     * @param string|null $tableName
     *
     * @return RecordInterface[]
     */
    public function findByProperties(array $properties, $simulateRoot = false, string $tableName = null): array
    {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        if ($simulateRoot) {
            $this->recordFactory->simulateRootRecord();
        }
        foreach ($properties as $propertyName => $propertyValue) {
            if ($this->shouldSkipFindByProperty($propertyName, $propertyValue)) {
                return [];
            }
        }
        if (isset($properties['uid'])
            && $record = $this->recordFactory->getCachedRecord($tableName, $properties['uid'])
        ) {
            return [$record];
        }
        $localProperties = $this->findPropertiesByProperties(
            $this->localDatabase,
            $properties,
            '',
            '',
            '',
            '',
            'uid',
            $tableName
        );
        $foreignProperties = $this->findPropertiesByProperties(
            $this->foreignDatabase,
            $properties,
            '',
            '',
            '',
            '',
            'uid',
            $tableName
        );
        $records = $this->convertPropertyArraysToRecords($localProperties, $foreignProperties, $tableName);
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
     * @param Connection $connection
     * @param string $tableName
     * @param string $propertyName
     * @param mixed $propertyValue
     * @param string $additionalWhere
     * @param string $groupBy
     * @param string $orderBy
     * @param string $limit
     * @param string $indexField
     *
     * @return array
     *
     * @deprecated CommonRepository::findPropertiesByPropertyAndTablename is deprecated and will be removed in
     *  in2publish_core version 10. Use BaseRepository::findPropertiesByProperty instead
     */
    protected function findPropertiesByPropertyAndTablename(
        Connection $connection,
        $tableName,
        $propertyName,
        $propertyValue,
        $additionalWhere = '',
        $groupBy = '',
        $orderBy = '',
        $limit = '',
        $indexField = 'uid'
    ): array {
        trigger_error(self::DEPRECATION_METHOD_FPBPATN, E_USER_DEPRECATED);
        $properties = $this->findPropertiesByProperty(
            $connection,
            $propertyName,
            $propertyValue,
            $additionalWhere,
            $groupBy,
            $orderBy,
            $limit,
            $indexField,
            $tableName
        );
        return $properties;
    }

    /**
     * Find the last record by property and table name
     *
     * @param Connection $connection
     * @param string $tableName
     * @param string $propertyName
     * @param mixed $propertyValue
     *
     * @return array
     */
    public function findLastPropertiesByPropertyAndTableName(
        Connection $connection,
        $tableName,
        $propertyName,
        $propertyValue
    ): array {
        $properties = $this->findPropertiesByProperty(
            $connection,
            $propertyName,
            $propertyValue,
            '',
            '',
            'uid desc',
            '1',
            'uid',
            $tableName
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
     * @param string|null $tableName
     *
     * @return RecordInterface[]
     */
    protected function convertPropertyArraysToRecords(
        array $localProperties,
        array $foreignProperties,
        string $tableName = null
    ): array {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        $keysToIterate = array_unique(array_merge(array_keys($localProperties), array_keys($foreignProperties)));

        $foundRecords = [];

        foreach ($keysToIterate as $key) {
            if (strpos((string)$key, ',') === false) {
                if (empty($localProperties[$key])) {
                    $propertyArray = $this->findPropertiesByProperty(
                        $this->localDatabase,
                        'uid',
                        $key,
                        '',
                        '',
                        '',
                        '',
                        'uid',
                        $tableName
                    );
                    if (!empty($propertyArray[$key])) {
                        $localProperties[$key] = $propertyArray[$key];
                    }
                }
                if (empty($foreignProperties[$key])) {
                    $propertyArray = $this->findPropertiesByProperty(
                        $this->foreignDatabase,
                        'uid',
                        $key,
                        '',
                        '',
                        '',
                        '',
                        'uid',
                        $tableName
                    );
                    if (!empty($propertyArray[$key])) {
                        $foreignProperties[$key] = $propertyArray[$key];
                        if ('sys_file_metadata' === $tableName
                            && isset($localProperties[$key]['file'])
                            && isset($foreignProperties[$key]['file'])
                            && (int)$localProperties[$key]['file'] !== (int)$foreignProperties[$key]['file']
                        ) {
                            // If the fixing of this relation results in a different related
                            // record we log it because it is very very very unlikely for
                            // sys_file_metadata to change their target sys_file entry
                            $this->logger->warning(
                                'Identified a sys_file_metadata for a file which has a different UID on foreign.'
                                . ' The foreign sys_file_metadata will be overwritten and therefore be lost',
                                [
                                    'table' => $tableName,
                                    'key (UID of the local record and the found foreign record)' => $key,
                                    'file_local' => $localProperties[$key]['file'],
                                    'file_foreign' => $foreignProperties[$key]['file'],
                                ]
                            );
                        }
                    }
                }
            }
            if (!$this->isIgnoredRecord((array)$localProperties[$key], (array)$foreignProperties[$key], $tableName)) {
                $foundRecords[$key] = $this->recordFactory->makeInstance(
                    $this,
                    (array)$localProperties[$key],
                    (array)$foreignProperties[$key],
                    [],
                    $tableName,
                    'uid'
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
     *
     * @return RecordInterface
     */
    public function enrichRecordWithRelatedRecords(RecordInterface $record, array $excludedTableNames): RecordInterface
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
            // TODO: Remove any `identifierFieldName` related stuff from this method with in2publish_core version 10.
            //  It is only required to maintain the function of the deprecated getter of this property.
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
                        $record,
                        $excludedTableNames,
                        $propertyName
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
                    $relatedRecords = [];
                    $bodyText = trim((string)$record->getLocalProperty($propertyName));
                    if (!empty($bodyText)) {
                        $relatedRecords = $this->fetchRelatedRecordsByRte($bodyText, $excludedTableNames);
                    }
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
            } catch (Throwable $e) {
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
     *
     * @return array
     */
    protected function fetchRelatedRecordsByRte(string $bodyText, array $excludedTableNames): array
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
                    foreach ($matches as $match) {
                        if (!empty($match)) {
                            // replace fileadmin if present. It has been replaced by the storage field (FAL)
                            if (strpos($match, 'fileadmin') === 0) {
                                $match = substr($match, 9);
                            }
                            $relatedProcFiles = $this->findByProperty('identifier', $match, 'sys_file_processedfile');
                            $relatedRecords = array_merge($relatedRecords, $relatedProcFiles);
                        }
                    }
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
                    foreach ($matches as $match) {
                        $relatedRecords[] = $this->findByIdentifier($match, 'sys_file');
                    }
                }
            }
        }
        if (strpos($bodyText, 't3://') !== false) {
            preg_match_all(self::REGEX_T3URN, $bodyText, $matches);
            if (!empty($matches['URN'])) {
                foreach ($matches['URN'] as $urn) {
                    // Do NOT use LinkService because the URN might either be not local or not available
                    $urnParsed = parse_url($urn);
                    parse_str(htmlspecialchars_decode($urnParsed['query']), $data);
                    switch ($urnParsed['host']) {
                        case 'file':
                            if (isset($data['uid'])) {
                                if (!in_array('sys_file', $excludedTableNames)) {
                                    $relatedRecords[] = $this->findByIdentifier($data['uid'], 'sys_file');
                                }
                            }
                            break;
                        case 'page':
                            if (isset($data['uid'])) {
                                if (!in_array('pages', $excludedTableNames)) {
                                    $relatedRecords[] = $this->findByIdentifier($data['uid'], 'pages');
                                }
                            }
                            break;
                        default:
                            // do not handle any other relation type
                    }
                }
            }
        }
        try {
            $this->signalSlotDispatcher->dispatch(
                CommonRepository::class,
                self::SIGNAL_RELATION_RESOLVER_RTE,
                [$this, $bodyText, $excludedTableNames, &$relatedRecords]
            );
        } catch (InvalidSlotException $e) {
            $this->logger->error(
                'Exception during signal dispatching',
                [
                    'exception' => $e,
                    'signalClass' => CommonRepository::class,
                    'signalName' => self::SIGNAL_RELATION_RESOLVER_RTE,
                ]
            );
        } catch (InvalidSlotReturnException $e) {
            $this->logger->error(
                'Exception during signal dispatching',
                [
                    'exception' => $e,
                    'signalClass' => CommonRepository::class,
                    'signalName' => self::SIGNAL_RELATION_RESOLVER_RTE,
                ]
            );
        }
        // Filter probable null values (e.g. the page linked in the TYPO3 URN is the page currently in enrichment mode)
        return array_filter($relatedRecords);
    }

    /**
     * finds and adds related records to pages. this is a special case, because any
     * related Record is found by its pid
     *
     * @param RecordInterface $record
     * @param array $excludedTableNames
     *
     * @return RecordInterface
     */
    public function enrichPageRecord(RecordInterface $record, array $excludedTableNames): RecordInterface
    {
        if ($this->shouldSkipEnrichingPageRecord($record)) {
            return $record;
        }
        $recordIdentifier = $record->getIdentifier();
        foreach ($this->tcaService->getAllTableNames($excludedTableNames) as $tableName) {
            if ($this->shouldSkipSearchingForRelatedRecordByTable($record, $tableName)) {
                continue;
            }
            $relatedRecords = $this->findByProperty('pid', $recordIdentifier, $tableName);
            $record->addRelatedRecords($relatedRecords);
        }
        return $record;
    }

    /**
     * Get flex form configuration from file or reference
     *
     * @param RecordInterface $record
     * @param string $column
     * @param array $columnConfiguration
     *
     * @return array
     * @throws InvalidParentRowException
     * @throws InvalidParentRowLoopException
     * @throws InvalidParentRowRootException
     * @throws InvalidPointerFieldValueException
     * @throws InvalidTcaException
     * @throws InvalidIdentifierException
     */
    protected function getFlexFormDefinition(RecordInterface $record, $column, array $columnConfiguration)
    {
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $dataStructIdentifier = $flexFormTools->getDataStructureIdentifier(
            ['config' => $columnConfiguration],
            $record->getTableName(),
            $column,
            $record->getLocalProperties()
        );
        $flexFormDefinition = $flexFormTools->parseDataStructureByIdentifier($dataStructIdentifier);
        $flexFormDefinition = $flexFormDefinition['sheets'];
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
     *
     * @return array
     */
    protected function flattenFlexFormDefinition(array $flexFormDefinition): array
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
     *
     * @return array
     */
    protected function flattenFieldFlexForm(array $flattenedDefinition, array $fieldDefinition, $fieldKey): array
    {
        // default FlexForm for a single field
        if (array_key_exists('TCEforms', $fieldDefinition)) {
            $flattenedDefinition[$fieldKey] = $fieldDefinition['TCEforms']['config'];
        } elseif (array_key_exists('el', $fieldDefinition)) {
            // advanced FlexForm for a single field with n subfields
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
        return $flattenedDefinition;
    }

    /**
     * @param array $flexFormDefinition
     *
     * @return array
     */
    protected function filterFlexFormDefinition(array $flexFormDefinition): array
    {
        foreach ($flexFormDefinition as $key => $config) {
            if (empty($config['type'])
                // Treat input and text always as field with relation because we can't access defaultExtras
                // settings here and better assume it's a RTE field
                || !in_array($config['type'], ['select', 'group', 'inline', 'input', 'text'])
            ) {
                unset($flexFormDefinition[$key]);
            }
        }
        return $flexFormDefinition;
    }

    /**
     * @param array $originalData
     * @param array $flexFormDefinition
     *
     * @return array
     */
    protected function getFlexFormDataByDefinition(array $originalData, array $flexFormDefinition): array
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
     *
     * @return mixed
     */
    protected function getValueByIndexStack(array $indexStack, array $data, array &$pathStack = [])
    {
        $workingData = $data;
        while ($index = array_shift($indexStack)) {
            if ($index === '[ANY]') {
                if (!is_array($workingData)) {
                    // $workingData is unpacked by the else part and can be any data type.
                    // If th index is [ANY] $workingData is expected to be an array.
                    // TYPO3 saves empty arrays as non self-closing <el> xml tags whose value parses to string,
                    // not back to empty arrays, that's why values can be string instead of array.
                    return null;
                }
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
     *
     * @return array
     */
    protected function getLocalFlexFormDataFromRecord(RecordInterface $record, $column): array
    {
        /** @var FlexFormService $flexFormService */
        // TODO: Replace with \TYPO3\CMS\Core\Service\FlexFormService upon dropping TYPO3 v8
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
     *
     * @return array
     * @throws Exception
     */
    protected function fetchRelatedRecordsByFlexForm(
        RecordInterface $record,
        $column,
        array $excludedTableNames,
        array $columnConfiguration
    ): array {
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

        if ($this->shouldSkipSearchingForRelatedRecordsByFlexForm(
            $record,
            $columnConfiguration,
            $flexFormDefinition,
            $flexFormData
        )) {
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
     *
     * @return array
     * @throws Exception
     */
    protected function getRecordsByFlexFormRelation(
        RecordInterface $record,
        $column,
        array $exclTables,
        $config,
        $flexFormData
    ): array {
        if ($this->shouldSkipSearchingForRelatedRecordsByFlexFormProperty($record, $config, $flexFormData)) {
            return [];
        }

        $records = [];
        $recTable = $record->getTableName();
        $recordId = $record->getIdentifier();
        switch ($config['type']) {
            case 'select':
                $records = $this->fetchRelatedRecordsBySelect($config, $record, $flexFormData, $exclTables, true);
                break;
            case 'inline':
                $records = $this->fetchRelatedRecordsByInline(
                    $config,
                    $recTable,
                    $record,
                    $exclTables,
                    $column,
                    $flexFormData
                );
                break;
            case 'group':
                $records = $this->fetchRelatedRecordsByGroup($config, $record, $column, $exclTables, $flexFormData);
                break;
            case 'text':
                // input and text are both treated as RTE
            case 'input':
                if (is_string($flexFormData)) {
                    $records = $this->fetchRelatedRecordsByRte($flexFormData, $exclTables);
                }
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
     *
     * @return RecordInterface[]
     */
    protected function fetchRelatedRecordsByGroupTypeDb(
        array $columnConfiguration,
        RecordInterface $record,
        array $excludedTableNames,
        $propertyName,
        array $overrideIdentifiers = []
    ): array {
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
                        $localProperties = $this->findPropertiesByProperty(
                            $this->localDatabase,
                            'uid',
                            $identifier,
                            '',
                            '',
                            '',
                            '',
                            'uid',
                            $tableName
                        );
                        $foreignProps = $this->findPropertiesByProperty(
                            $this->foreignDatabase,
                            'uid',
                            $identifier,
                            '',
                            '',
                            '',
                            '',
                            'uid',
                            $tableName
                        );
                        $found = $this->convertPropertyArraysToRecords($localProperties, $foreignProps, $tableName);
                        $records = array_merge($records, $found);
                    }
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
                $mmTableName = $columnConfiguration['MM'];
                $localProperties = $this->findPropertiesByProperty(
                    $this->localDatabase,
                    $this->getLocalField($columnConfiguration),
                    $record->getIdentifier(),
                    '',
                    '',
                    '',
                    '',
                    'uid_local,uid_foreign',
                    $mmTableName
                );
                $foreignProperties = $this->findPropertiesByProperty(
                    $this->foreignDatabase,
                    $this->getLocalField($columnConfiguration),
                    $record->getIdentifier(),
                    '',
                    '',
                    '',
                    '',
                    'uid_local,uid_foreign',
                    $mmTableName
                );
                $records = $this->convertPropertyArraysToRecords($localProperties, $foreignProperties, $mmTableName);
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
                                'recordTableName' => $originalTableName,
                                'relatedRecordIdentifier' => $relatedRecord->getIdentifier(),
                            ]
                        );
                        continue;
                    }
                    if (!in_array($originalTableName, $excludedTableNames)) {
                        $originalRecord = $this->findByIdentifier($localUid, $originalTableName);
                        if ($originalRecord !== null) {
                            $relatedRecord->addRelatedRecord($originalRecord);
                        }
                    }
                }
            } else {
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
                    $localProperties = $this->findPropertiesByProperty(
                        $this->localDatabase,
                        'uid',
                        $identifier,
                        '',
                        '',
                        '',
                        '',
                        'uid',
                        $tableName
                    );
                    $foreignProperties = $this->findPropertiesByProperty(
                        $this->foreignDatabase,
                        'uid',
                        $identifier,
                        '',
                        '',
                        '',
                        '',
                        'uid',
                        $tableName
                    );
                    $found = $this->convertPropertyArraysToRecords($localProperties, $foreignProperties, $tableName);
                    $records = array_merge($records, $found);
                }
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
     *
     * @return array
     * @throws Exception
     */
    protected function fetchRelatedRecordsByGroup(
        array $columnConfiguration,
        RecordInterface $record,
        $propertyName,
        array $excludedTableNames,
        $flexFormData = ''
    ): array {
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
                    $record = $this->findByIdentifier($fileAndPathName, 'sys_file', 'identifier');
                    if ($record instanceof RecordInterface) {
                        $recordIdentifier = $record->getIdentifier();

                        // special case: the record exists only in the local database and the same uid
                        // is existent in the foreign table, but not with the given identifier
                        // Solution: Re-fetch the record by its UID, so we ensure we can overwrite the foreign record,
                        // given the relation is broken
                        if (RecordInterface::RECORD_STATE_ADDED === $record->getState()) {
                            $this->recordFactory->forgetCachedRecord('sys_file', $recordIdentifier);
                            $record = $this->findByIdentifier($recordIdentifier, 'sys_file');
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
     *
     * @return array
     */
    protected function getFileAndPathNames(
        array $columnConfiguration,
        RecordInterface $record,
        $propertyName,
        $flexFormData
    ): array {
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
     *
     * @return array
     */
    protected function fetchRelatedRecordsBySelect(
        array $columnConfiguration,
        RecordInterface $record,
        $propertyName,
        array $excludedTableNames,
        $overrideIdByRecord = false
    ): array {
        $tableName = $columnConfiguration['foreign_table'];
        // FlexForms without `foreign_table` sneak through the TCA pre processing
        if (empty($tableName) || in_array($tableName, $excludedTableNames)) {
            return [];
        }
        $records = [];

        if ($overrideIdByRecord) {
            $recordIdentifier = $propertyName;
        } else {
            $recordIdentifier = $record->getMergedProperty($propertyName);
        }

        if ($recordIdentifier !== null && trim((string)$recordIdentifier) !== '' && (int)$recordIdentifier > 0) {
            // if the relation is an MM type, then select all identifiers from the MM table
            if (!empty($columnConfiguration['MM'])) {
                $records = $this->fetchRelatedRecordsBySelectMm($columnConfiguration, $record, $excludedTableNames);
            } else {
                $whereClause = '';
                if (!empty($columnConfiguration['foreign_table_where'])) {
                    /** @var ReplaceMarkersService $replaceMarkers */
                    $replaceMarkers = GeneralUtility::makeInstance(ReplaceMarkersService::class);
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
                    $localProperties = $this->findPropertiesByProperty(
                        $this->localDatabase,
                        'uid',
                        $uid,
                        $whereClause,
                        '',
                        '',
                        '',
                        'uid',
                        $tableName
                    );
                    $foreignProperties = $this->findPropertiesByProperty(
                        $this->foreignDatabase,
                        'uid',
                        $uid,
                        $whereClause,
                        '',
                        '',
                        '',
                        'uid',
                        $tableName
                    );
                    $found = $this->convertPropertyArraysToRecords($localProperties, $foreignProperties, $tableName);
                    $records = array_merge($records, $found);
                }
            }
        }
        return $records;
    }

    /**
     * @param array $columnConfiguration
     * @param RecordInterface $record
     * @param array $excludedTableNames
     *
     * @return array
     */
    protected function fetchRelatedRecordsBySelectMm(
        array $columnConfiguration,
        RecordInterface $record,
        array $excludedTableNames
    ): array {
        $tableName = $columnConfiguration['MM'];

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
        $localProperties = $this->findPropertiesByProperty(
            $this->localDatabase,
            $this->getLocalField($columnConfiguration),
            $record->getIdentifier(),
            $additionalWhere,
            '',
            '',
            '',
            'uid_local,uid_foreign',
            $tableName
        );
        $foreignProperties = $this->findPropertiesByProperty(
            $this->foreignDatabase,
            $this->getLocalField($columnConfiguration),
            $record->getIdentifier(),
            $additionalWhere,
            '',
            '',
            '',
            'uid_local,uid_foreign',
            $tableName
        );
        $records = $this->convertPropertyArraysToRecords($localProperties, $foreignProperties, $tableName);

        $foreignField = $this->getForeignField($columnConfiguration);

        /** @var RecordInterface $relationRecord */
        foreach ($records as $relationRecord) {
            $originalTableName = $columnConfiguration['foreign_table'];
            if (!in_array($originalTableName, $excludedTableNames)) {
                $identifier = $relationRecord->getMergedProperty($foreignField);
                $originalRecord = $this->findByIdentifier($identifier, $originalTableName);
                if ($originalRecord !== null) {
                    $relationRecord->addRelatedRecord($originalRecord);
                }
            }
        }

        return $records;
    }

    /**
     * Fetches inline related Records like FAL images. Often used in FAL
     * or custom Extensions. Lacks support for MM tables
     *
     * @param array $columnConfiguration
     * @param string $recordTableName
     * @param RecordInterface $record
     * @param array $excludedTableNames
     * @param string $propertyName
     * @param string|null $flexFormData
     *
     * @return array
     */
    protected function fetchRelatedRecordsByInline(
        array $columnConfiguration,
        $recordTableName,
        RecordInterface $record,
        array $excludedTableNames,
        string $propertyName,
        string $flexFormData = null
    ): array {
        $recordIdentifier = $record->getIdentifier();
        $tableName = $columnConfiguration['foreign_table'];
        if (in_array($tableName, $excludedTableNames)) {
            return [];
        }

        $where = [];

        if (!empty($columnConfiguration['MM'])) {
            $records = $this->fetchRelatedRecordsByInlineMm(
                $columnConfiguration,
                $recordTableName,
                $recordIdentifier,
                $excludedTableNames
            );
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

        if (empty($columnConfiguration['foreign_field'])) {
            $records = [];
            if (empty($flexFormData)) {
                $localList = $record->getLocalProperty($propertyName);
                $localList = GeneralUtility::trimExplode(',', $localList, true);
                $foreignList = $record->getForeignProperty($propertyName);
                $foreignList = GeneralUtility::trimExplode(',', $foreignList, true);
                $identifierList = array_unique(array_merge($localList, $foreignList));
            } else {
                $identifierList = GeneralUtility::intExplode(',', $flexFormData);
            }
            foreach ($identifierList as $uid) {
                $records[] = $this->findByIdentifier((int)$uid, $tableName);
            }
            return $records;
        }

        $foreignField = $columnConfiguration['foreign_field'];

        $localProperties = $this->findPropertiesByProperty(
            $this->localDatabase,
            $foreignField,
            $recordIdentifier,
            $whereClause,
            '',
            '',
            '',
            'uid',
            $tableName
        );
        $foreignProperties = $this->findPropertiesByProperty(
            $this->foreignDatabase,
            $foreignField,
            $recordIdentifier,
            $whereClause,
            '',
            '',
            '',
            'uid',
            $tableName
        );
        $records = $this->convertPropertyArraysToRecords($localProperties, $foreignProperties, $tableName);

        return $records;
    }

    /**
     * @param array $columnConfiguration
     * @param $recordTableName
     * @param $recordIdentifier
     * @param array $excludedTableNames
     *
     * @return array
     */
    protected function fetchRelatedRecordsByInlineMm(
        array $columnConfiguration,
        $recordTableName,
        $recordIdentifier,
        array $excludedTableNames
    ): array {
        if (!empty($columnConfiguration['foreign_field'])
            || !empty($columnConfiguration['foreign_selector'])
            || !empty($columnConfiguration['filter'])
            || !empty($columnConfiguration['foreign_types'])
            || !empty($columnConfiguration['foreign_table_field'])
        ) {
            $this->logger->error(
                'Inline MM relations with foreign_field, foreign_types, symmetric_field, filter, '
                . 'foreign_table_field or foreign_selector are not supported',
                [
                    'columnConfiguration' => $columnConfiguration,
                    'recordTableName' => $recordTableName,
                    'recordIdentifier' => $recordIdentifier,
                ]
            );
        }
        $tableName = $columnConfiguration['MM'];
        $localProperties = $this->findPropertiesByProperty(
            $this->localDatabase,
            'uid_local',
            $recordIdentifier,
            '',
            '',
            '',
            '',
            'uid_local,uid_foreign',
            $tableName
        );
        $foreignProperties = $this->findPropertiesByProperty(
            $this->foreignDatabase,
            'uid_local',
            $recordIdentifier,
            '',
            '',
            '',
            '',
            'uid_local,uid_foreign',
            $tableName
        );
        $relationRecords = $this->convertPropertyArraysToRecords($localProperties, $foreignProperties, $tableName);
        $this->fetchOriginalRecordsForInlineRecord(
            $relationRecords,
            $columnConfiguration,
            $recordTableName,
            $recordIdentifier,
            $excludedTableNames
        );
        return $relationRecords;
    }

    /**
     * @param RecordInterface[] $relationRecords
     * @param array $columnConfiguration
     * @param string $recordTableName
     * @param string $recordIdentifier
     * @param array $excludedTableNames
     *
     * @return array
     */
    protected function fetchOriginalRecordsForInlineRecord(
        array $relationRecords,
        array $columnConfiguration,
        $recordTableName,
        $recordIdentifier,
        array $excludedTableNames
    ): array {
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
                $originalRecord = $this->findByIdentifier($localUid, $columnConfiguration['foreign_table']);
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
     *
     * @return RecordInterface|null
     *
     * @deprecated Method will be removed in in2publish_core version 10. Use `findByIdentifier` instead.
     */
    protected function findByIdentifierInOtherTable($identifier, $tableName)
    {
        trigger_error(
            sprintf(self::DEPRECATION_METHOD, __METHOD__) . ' Use `findByIdentifier` instead.',
            E_USER_DEPRECATED
        );
        return $this->findByIdentifier($identifier, $tableName);
    }

    /**
     * Check if this record should be ignored
     *
     * @param array $localProperties
     * @param array $foreignProperties
     * @param string|null $tableName
     *
     * @return bool
     */
    protected function isIgnoredRecord(array $localProperties, array $foreignProperties, string $tableName = null): bool
    {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        if ($this->isDeletedAndUnchangedRecord($localProperties, $foreignProperties)
            || $this->isRemovedAndDeletedRecord($localProperties, $foreignProperties)
            || $this->shouldIgnoreRecord($localProperties, $foreignProperties, $tableName)
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
     *
     * @return bool
     */
    protected function isRemovedAndDeletedRecord(array $localProps, array $foreignProps): bool
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
     *
     * @return bool
     */
    protected function isDeletedAndUnchangedRecord(array $localProperties, array $foreignProperties): bool
    {
        return $localProperties['deleted'] === '1' && !count(array_diff($localProperties, $foreignProperties));
    }

    /**
     * Publishes the given Record and all related Records
     * where the related Record's tableName is not excluded
     *
     * @param RecordInterface $record
     * @param array $excludedTables
     *
     * @return void
     * @throws Throwable
     */
    public function publishRecordRecursive(RecordInterface $record, array $excludedTables = ['pages'])
    {
        try {
            // Dispatch Anomaly
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'publishRecordRecursiveBegin',
                [$record, $this]
            );

            $this->publishRecordRecursiveInternal($record, $excludedTables);

            // Dispatch Anomaly
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'publishRecordRecursiveEnd',
                [$record, $this]
            );
        } catch (Throwable $exception) {
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
     *
     * @throws InvalidSlotException
     * @throws InvalidSlotReturnException
     */
    protected function publishRecordRecursiveInternal(RecordInterface $record, array $excludedTables)
    {
        $tableName = $record->getTableName();

        if (!empty($this->visitedRecords[$tableName])) {
            if (in_array($record->getIdentifier(), $this->visitedRecords[$tableName])) {
                return;
            }
        }
        $this->visitedRecords[$tableName][] = $record->getIdentifier();

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
                        'tableName' => $tableName,
                        'local_uid' => $record->getLocalProperty('uid'),
                        'foreign_uid' => $record->getForeignProperty('uid'),
                    ]
                );
                // remove duplicate remote index
                $this->deleteRecord($this->foreignDatabase, $record->getForeignProperty('uid'), $tableName);
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
        $this->publishRelatedRecordsRecursive($record, $excludedTables);
    }

    /**
     * Publishes all related Records of the given record if
     * their tableName is not included in $excludedTables
     *
     * @param RecordInterface $record
     * @param array $excludedTables
     *
     * @return void
     */
    protected function publishRelatedRecordsRecursive(RecordInterface $record, array $excludedTables)
    {
        foreach ($record->getRelatedRecords() as $tableName => $relatedRecords) {
            if (!in_array($tableName, $excludedTables) && is_array($relatedRecords)) {
                /** @var RecordInterface $relatedRecord */
                foreach ($relatedRecords as $relatedRecord) {
                    $this->publishRecordRecursiveInternal($relatedRecord, $excludedTables);
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
     * @param string|null $tableName
     * @param string $idFieldName
     *
     * @return RecordInterface|null
     *
     * @deprecated This method will be removed in in2publish_core version 10. Use `$this->recordFactory->makeInstance`.
     */
    protected function convertToRecord(
        array $localProperties,
        array $foreignProperties,
        string $tableName = null,
        string $idFieldName = 'uid'
    ) {
        trigger_error(sprintf(static::DEPRECATION_METHOD, __METHOD__), E_USER_DEPRECATED);
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        return $this->recordFactory->makeInstance(
            $this,
            $localProperties,
            $foreignProperties,
            [],
            $tableName,
            $idFieldName
        );
    }

    /**
     * Publishing Method: Executes an UPDATE query on the
     * foreign Database with all record properties
     *
     * @param RecordInterface $record
     *
     * @return void
     */
    protected function updateForeignRecord(RecordInterface $record)
    {
        $identifier = $record->getIdentifier();
        $properties = $record->getLocalProperties();
        $tableName = $record->getTableName();
        $this->updateRecord($this->foreignDatabase, $identifier, $properties, $tableName);
    }

    /**
     * Publishing Method: Executes an INSERT query on the
     * foreign database with all record properties
     *
     * @param RecordInterface $record
     *
     * @return void
     */
    protected function addForeignRecord(RecordInterface $record)
    {
        $tableName = $record->getTableName();
        $properties = $record->getLocalProperties();
        $this->addRecord($this->foreignDatabase, $properties, $tableName);
    }

    /**
     * Publishing Method: Removes a row from the foreign database
     * This can not be undone and the row will be removed forever
     *
     * Since this action is highly destructive, it
     * must be enabled in the Configuration
     *
     * @param RecordInterface $record
     *
     * @return void
     */
    protected function deleteForeignRecord(RecordInterface $record)
    {
        $identifier = $record->getIdentifier();
        $tableName = $record->getTableName();
        $this->logger->notice(
            'Deleting foreign record',
            [
                'localProperties' => $record->getLocalProperties(),
                'foreignProperties' => $record->getForeignProperties(),
                'identifier' => $identifier,
                'tableName' => $tableName,
            ]
        );
        $this->deleteRecord($this->foreignDatabase, $identifier, $tableName);
    }

    /**
     * Get local field for mm tables (and switch name if "MM_opposite_field" is set, should be set for categories only)
     *
     * @param array $columnConfiguration
     *
     * @return string
     */
    protected function getLocalField(array $columnConfiguration): string
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
     *
     * @return string
     */
    protected function getForeignField(array $columnConfiguration): string
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
     * @param string $identifier
     * @param string|null $tableName
     *
     * @return bool
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     */
    protected function shouldSkipFindByIdentifier($identifier, string $tableName = null): bool
    {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        return $this->should('shouldSkipFindByIdentifier', ['identifier' => $identifier, 'tableName' => $tableName]);
    }

    /**
     * @param string $propertyName
     * @param mixed $propertyValue
     *
     * @return bool
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     */
    protected function shouldSkipFindByProperty($propertyName, $propertyValue): bool
    {
        $arguments = ['propertyName' => $propertyName, 'propertyValue' => $propertyValue];
        return $this->should('shouldSkipFindByProperty', $arguments);
    }

    /**
     * @param RecordInterface $record
     *
     * @return bool
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     */
    protected function shouldSkipSearchingForRelatedRecords(RecordInterface $record): bool
    {
        return $this->should('shouldSkipSearchingForRelatedRecords', ['record' => $record]);
    }

    /**
     * @param RecordInterface $record
     * @param string $propertyName
     * @param array $columnConfiguration
     *
     * @return bool
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     */
    protected function shouldSkipSearchingForRelatedRecordsByProperty(
        RecordInterface $record,
        $propertyName,
        array $columnConfiguration
    ): bool {
        $arguments = [
            'record' => $record,
            'propertyName' => $propertyName,
            'columnConfiguration' => $columnConfiguration,
        ];
        return $this->should('shouldSkipSearchingForRelatedRecordsByProperty', $arguments);
    }

    /**
     * @param RecordInterface $record
     * @param array $columnConfiguration
     * @param array $flexFormDefinition
     * @param array $flexFormData
     *
     * @return bool
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     */
    protected function shouldSkipSearchingForRelatedRecordsByFlexForm(
        RecordInterface $record,
        $columnConfiguration,
        $flexFormDefinition,
        $flexFormData
    ): bool {
        $arguments = [
            'record' => $record,
            'columnConfiguration' => $columnConfiguration,
            'flexFormDefinition' => $flexFormDefinition,
            'flexFormData' => $flexFormData,
        ];
        return $this->should('shouldSkipSearchingForRelatedRecordsByFlexForm', $arguments);
    }

    /**
     * @param RecordInterface $record
     * @param array $config
     * @param array $flexFormData
     *
     * @return bool
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     */
    protected function shouldSkipSearchingForRelatedRecordsByFlexFormProperty(
        RecordInterface $record,
        $config,
        $flexFormData
    ): bool {
        $arguments = [
            'record' => $record,
            'config' => $config,
            'flexFormData' => $flexFormData,
        ];
        return $this->should('shouldSkipSearchingForRelatedRecordsByFlexFormProperty', $arguments);
    }

    /**
     * @param RecordInterface $record
     *
     * @return bool
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     */
    protected function shouldSkipEnrichingPageRecord(RecordInterface $record): bool
    {
        return $this->should('shouldSkipEnrichingPageRecord', ['record' => $record]);
    }

    /**
     * @param RecordInterface $record
     * @param string $tableName
     *
     * @return bool
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     */
    protected function shouldSkipSearchingForRelatedRecordByTable(RecordInterface $record, $tableName): bool
    {
        return $this->should(
            'shouldSkipSearchingForRelatedRecordByTable',
            ['record' => $record, 'tableName' => $tableName]
        );
    }

    /**
     * @param RecordInterface $record
     * @param string $tableName
     *
     * @return bool
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     *
     */
    protected function shouldSkipRecord(RecordInterface $record, $tableName): bool
    {
        return $this->should('shouldSkipRecord', ['record' => $record, 'tableName' => $tableName]);
    }

    /**
     * @param array $localProperties
     * @param array $foreignProperties
     * @param string|null $tableName
     *
     * @return bool
     * @see \In2code\In2publishCore\Domain\Repository\CommonRepository::should
     */
    protected function shouldIgnoreRecord(
        array $localProperties,
        array $foreignProperties,
        string $tableName = null
    ): bool {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_TABLE_NAME_FIELD, __METHOD__), E_USER_DEPRECATED);
            $tableName = $this->tableName;
        }
        return $this->should(
            'shouldIgnoreRecord',
            [
                'localProperties' => $localProperties,
                'foreignProperties' => $foreignProperties,
                'tableName' => $tableName,
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
     *
     * @return bool If no vote was received false will be returned
     */
    protected function should($signal, array $arguments): bool
    {
        $signalArguments = $this->signalSlotDispatcher->dispatch(
            __CLASS__,
            $signal,
            [['yes' => 0, 'no' => 0], $this, $arguments]
        );
        return $signalArguments[0]['yes'] > $signalArguments[0]['no'];
    }

    /**
     * @param string|null $tableName
     *
     * @return CommonRepository
     */
    public static function getDefaultInstance($tableName = null): CommonRepository
    {
        if (null !== $tableName) {
            trigger_error(sprintf(self::DEPRECATION_PARAMETER, 'tableName', __METHOD__), E_USER_DEPRECATED);
        }
        return GeneralUtility::makeInstance(
            CommonRepository::class,
            DatabaseUtility::buildLocalDatabaseConnection(),
            DatabaseUtility::buildForeignDatabaseConnection(),
            $tableName
        );
    }
}
