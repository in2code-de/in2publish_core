<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Factory;

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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\NullRecord;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Service\Configuration\TcaService;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

use function array_diff;
use function array_filter;
use function array_merge;
use function in_array;
use function sprintf;
use function strlen;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * RecordFactory: This class is responsible for create instances of Record.
 * This class is called recursively for the record, any related
 * records and any of the related records related records and so on to the extend
 * of the setting maximumRecursionDepth
 */
class RecordFactory
{
    public const DEPRECATION_METHOD_NO_TABLE_ARG = 'Calling %s without tableName is deprecated. tableName will be a non-optional argument in in2publish_core version 10.';

    /**
     * Runtime cache to cache already created Records
     * Structure:
     *  1. Index: TableName
     *  2. Index: UID
     *  3. Value: Record Object
     *
     * @var RecordInterface[][]
     */
    protected $runtimeCache = [];

    /**
     * Array of $tableName => array($uid, $uid) entries
     * indicating if the record to instantiate is already
     * in instantiation
     *
     * @var array
     */
    protected $instantiationQueue = [];

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var array
     */
    protected $config = [
        'maximumPageRecursion' => 0,
        'maximumContentRecursion' => 0,
        'maximumOverallRecursion' => 0,
        'resolvePageRelations' => true,
        'includeSysFileReference' => false,
    ];

    /**
     * current recursion depth of makeInstance
     *
     * @var int
     */
    protected $currentDepth = 0;

    /**
     * array of table names to be excluded from publishing
     * currently only used for related records of "page records"
     *
     * @var array
     */
    protected $excludedTableNames = [];

    /**
     * current depth of related page records
     *
     * @var int
     */
    protected $pagesDepth = 1;

    /**
     * current depth of related content records (anything but page)
     *
     * @var int
     */
    protected $relatedRecordsDepth = 1;

    /**
     * @var bool
     */
    protected $pageRecursionEnabled = true;

    /**
     * @var TcaService
     */
    protected $tcaService = null;

    /**
     * @var Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @var bool
     */
    protected $isRootRecord = false;

    /**
     * Creates the logger and sets any required configuration
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);
        $this->signalSlotDispatcher = GeneralUtility::makeInstance(Dispatcher::class);

        $this->config = GeneralUtility::makeInstance(ConfigContainer::class)->get('factory');

        $this->config['maximumOverallRecursion'] = max(
            $this->config['maximumOverallRecursion'],
            $this->config['maximumPageRecursion'] + $this->config['maximumContentRecursion']
        );

        $this->excludedTableNames = GeneralUtility::makeInstance(ConfigContainer::class)->get('excludeRelatedTables');
    }

    /**
     * Creates a fresh instance of a record and sets all related Records.
     *
     * @param CommonRepository $commonRepository Needed for recursion
     * @param array $localProperties Properties of the record from local Database
     * @param array $foreignProperties Properties of the record from foreign Database
     * @param array $additionalProperties array of not persisted properties
     * @param string|null $tableName
     * @param string $idFieldName
     *
     * @return RecordInterface|null
     */
    public function makeInstance(
        CommonRepository $commonRepository,
        array $localProperties,
        array $foreignProperties,
        array $additionalProperties = [],
        string $tableName = null,
        string $idFieldName = 'uid'
    ) {
        if (false === $this->isRootRecord) {
            $this->isRootRecord = true;
            $isRootRecord = true;
        } else {
            $isRootRecord = false;
        }
        // one of the property arrays might be empty,
        // to get the identifier we have to take a look into both arrays
        $mergedIdentifier = $this->getMergedIdentifierValue(
            $commonRepository,
            $localProperties,
            $foreignProperties,
            $tableName,
            $idFieldName
        );

        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_METHOD_NO_TABLE_ARG, __METHOD__), E_USER_DEPRECATED);
            $tableName = $commonRepository->getTableName();
        }

        // detects if an instance has been moved upwards or downwards
        // a hierarchy, corrects the relations and sets the records state to "moved"
        $hasBeenMoved = $this->detectAndAlterMovedInstance(
            $tableName,
            $mergedIdentifier,
            $localProperties,
            $foreignProperties
        );

        // internal cache: if the record has been instantiated already
        // it will set in here. This ensures a singleton
        if (($tableName === 'pages' || $mergedIdentifier > 0)
            && !empty($this->runtimeCache[$tableName][$mergedIdentifier])
        ) {
            $instance = $this->runtimeCache[$tableName][$mergedIdentifier];
        } else {
            // detect if the Record to instantiate is already in instantiation
            if ($this->isLooping($tableName, $mergedIdentifier)) {
                return null;
            }

            $depth = $tableName === 'pages' ? $this->pagesDepth : $this->relatedRecordsDepth;
            $additionalProperties += ['depth' => $depth];

            // do not use objectManager->get because of performance issues. Additionally,
            // we just do not need it, because there is no dependency injection
            $instance = GeneralUtility::makeInstance(
                Record::class,
                $tableName,
                $localProperties,
                $foreignProperties,
                (array)$this->tcaService->getConfigurationArrayForTable($tableName),
                $additionalProperties
            );
            if (true === $isRootRecord && true === $this->isRootRecord) {
                $instance->addAdditionalProperty('isRoot', true);
            }

            if ($instance->getIdentifier() !== 0
                && !$instance->localRecordExists()
                && !$instance->foreignRecordExists()
            ) {
                return $instance;
            }

            if ($hasBeenMoved && !$instance->isChanged()) {
                $instance->setState(RecordInterface::RECORD_STATE_MOVED);
            }

            try {
                $this->signalSlotDispatcher->dispatch(__CLASS__, 'instanceCreated', [$this, $instance]);
            } catch (InvalidSlotException $e) {
            } catch (InvalidSlotReturnException $e) {
            }

            /* special case of tables without TCA (currently only sys_file_processedfile).
             * Normally we would just ignore them, but:
             *      sys_file_processedfile:
             *          needed for RTE magic images - relation to original image
             */
            $tableConfiguration = $this->tcaService->getConfigurationArrayForTable($tableName);
            if (empty($tableConfiguration)) {
                switch ($tableName) {
                    case 'sys_file_processedfile':
                        $identifier = $instance->getLocalProperty('original');
                        $record = $commonRepository->findByIdentifier($identifier, 'sys_file');
                        $instance->addRelatedRecord($record);
                        break;
                    default:
                }
            } elseif ($this->currentDepth < $this->config['maximumOverallRecursion']) {
                $this->currentDepth++;
                if ($tableName === 'pages') {
                    $instance = $this->findRelatedRecordsForPageRecord($instance, $commonRepository);
                } else {
                    $instance = $this->findRelatedRecordsForContentRecord($instance, $commonRepository);
                }
                $this->currentDepth--;
            } else {
                $this->logger->emergency(
                    'Reached maximumOverallRecursion. This should not happen since maximumOverallRecursion ' .
                    'is considered deprecated and will be removed',
                    [
                        'table' => $instance->getTableName(),
                        'depth' => $instance->getAdditionalProperty('depth'),
                        'currentOverallRecursion' => $this->currentDepth,
                        'maximumOverallRecursion' => $this->config['maximumOverallRecursion'],
                    ]
                );
            }
            $this->finishedInstantiation($tableName, $mergedIdentifier);
            $this->runtimeCache[$tableName][$mergedIdentifier] = $instance;
        }
        if (true === $isRootRecord && true === $this->isRootRecord) {
            $this->isRootRecord = false;
            try {
                $this->signalSlotDispatcher->dispatch(__CLASS__, 'rootRecordFinished', [$this, $instance]);
            } catch (InvalidSlotException $e) {
            } catch (InvalidSlotReturnException $e) {
            }
        }
        return $instance;
    }

    /**
     * @param RecordInterface $record
     * @param CommonRepository $commonRepository
     *
     * @return RecordInterface
     */
    protected function findRelatedRecordsForContentRecord(
        RecordInterface $record,
        CommonRepository $commonRepository
    ): RecordInterface {
        if ($this->relatedRecordsDepth < $this->config['maximumContentRecursion']) {
            $this->relatedRecordsDepth++;
            $excludedTableNames = $this->excludedTableNames;
            if (false === $this->config['resolvePageRelations']) {
                $excludedTableNames[] = 'pages';
            }

            $this->findTranslations($record, $commonRepository);

            $record = $commonRepository->enrichRecordWithRelatedRecords($record, $excludedTableNames);
            $this->relatedRecordsDepth--;
        }
        return $record;
    }

    /**
     * @param Record $record
     * @param CommonRepository $commonRepository
     *
     * @return RecordInterface
     */
    protected function findRelatedRecordsForPageRecord(
        Record $record,
        CommonRepository $commonRepository
    ): RecordInterface {
        if ($record->getIdentifier() === 0) {
            $tableNamesToExclude =
                array_merge(
                    array_diff(
                        $this->tcaService->getAllTableNames(),
                        $this->tcaService->getAllTableNamesAllowedOnRootLevel()
                    ),
                    $this->excludedTableNames,
                    ['sys_file', 'sys_file_metadata']
                );
        } else {
            $tableNamesToExclude = $this->excludedTableNames;
        }
        // Special excluded table for page to table relation because this MM table has a PID (for whatever reason).
        // The relation should come from the record via TCA not via the PID relation to the page.
        if (!$this->config['includeSysFileReference']) {
            $tableNamesToExclude[] = 'sys_file_reference';
        }
        // if page recursion depth reached
        if ($this->pagesDepth < $this->config['maximumPageRecursion'] && $this->pageRecursionEnabled) {
            $this->pagesDepth++;
            $record = $commonRepository->enrichPageRecord($record, $tableNamesToExclude);
            $this->pagesDepth--;
        } else {
            // get related records without table pages
            $tableNamesToExclude[] = 'pages';
            $record = $commonRepository->enrichPageRecord($record, $tableNamesToExclude);
        }
        $relatedRecordsDepth = $this->relatedRecordsDepth;
        $this->relatedRecordsDepth = 0;
        $record = $this->findRelatedRecordsForContentRecord($record, $commonRepository);
        $this->relatedRecordsDepth = $relatedRecordsDepth;
        return $record;
    }

    /**
     * Detects a moved instance depending on some factors
     * 1. The instance must have been created earlier
     * 2. it has either local or foreign properties
     * 3. the record has been modified (could be deleted, added or changed)
     * 4. the second instantiation will try to instantiate
     *      the record with the missing properties
     *
     * OR:
     * 5. The Instance should be created with different PIDs
     *
     * @param string $tableName
     * @param string $identifier
     * @param array $localProperties
     * @param array $foreignProperties
     *
     * @return bool
     */
    protected function detectAndAlterMovedInstance(
        $tableName,
        $identifier,
        array $localProperties,
        array $foreignProperties
    ): bool {
        $hasBeenMoved = false;
        // 1. it was created already
        if (!empty($this->runtimeCache[$tableName][$identifier]) && !in_array($tableName, $this->excludedTableNames)) {
            $record = $this->runtimeCache[$tableName][$identifier];
            // consequence of 5.
            if ($record->getState() === RecordInterface::RECORD_STATE_MOVED) {
                $localPid = $record->getLocalProperty('pid');
                $parentRecord = $record->getParentRecord();
                if ($parentRecord instanceof RecordInterface) {
                    // if the parent is set correctly
                    if ((int)$parentRecord->getIdentifier() === (int)$localPid) {
                        $record->lockParentRecord();
                    } else {
                        // wrong parent
                        $record->getParentRecord()->removeRelatedRecord($record);
                    }
                }
                // 3. it is modified
            } elseif ($record->getState() !== RecordInterface::RECORD_STATE_UNCHANGED) {
                if ($record->foreignRecordExists() && empty($foreignProperties) && !empty($localProperties)) {
                    // 2. it has only foreign properties && 4. the missing properties are given
                    // the current relation is set wrong. This record is referenced
                    // by the record which is parent on the foreign side
                    $record->getParentRecord()->removeRelatedRecord($record);
                    $record->setLocalProperties($localProperties);
                    $hasBeenMoved = true;
                } elseif ($record->localRecordExists() && empty($localProperties) && !empty($foreignProperties)) {
                    // 2. it has only local properties && 4. the missing properties are given
                    $record->setForeignProperties($foreignProperties);
                    // the current parent is correct, prevent changes to
                    // parentRecord or adding the record to other records as relation
                    $record->lockParentRecord();
                    $hasBeenMoved = true;
                }
                if ($hasBeenMoved) {
                    // re-calculate dirty properties
                    $record->setDirtyProperties();
                    $record->setState(RecordInterface::RECORD_STATE_MOVED);
                } elseif (!$record->isChanged()) {
                    if ($record->getLocalProperty('sorting') !== $record->getForeignProperty('sorting')) {
                        $hasBeenMoved = true;
                    }
                }
            }
            //  5. The Instance should be created with different PIDs
        } elseif (!empty($localProperties['pid']) && !empty($foreignProperties['pid'])) {
            if ($localProperties['pid'] !== $foreignProperties['pid']) {
                $hasBeenMoved = true;
            } elseif ($localProperties['sorting'] !== $foreignProperties['sorting']) {
                $hasBeenMoved = true;
            }
        }
        return $hasBeenMoved;
    }

    /**
     * gets the field name of the identifier field and
     * checks both arrays for an exiting identity value
     *
     * @param CommonRepository $commonRepository
     * @param array $localProperties
     * @param array $foreignProperties
     * @param string|null $tableName
     * @param string $idFieldName
     *
     * @return int|string
     */
    protected function getMergedIdentifierValue(
        $commonRepository,
        array $localProperties,
        array $foreignProperties,
        string $tableName = null,
        string $idFieldName = 'uid'
    ) {
        if (null === $tableName) {
            trigger_error(sprintf(static::DEPRECATION_METHOD_NO_TABLE_ARG, __METHOD__), E_USER_DEPRECATED);
            $tableName = $commonRepository->getTableName();
        }
        if ($tableName === 'sys_file') {
            $idFieldName = 'uid';
        }
        if (!empty($localProperties[$idFieldName])) {
            return $localProperties[$idFieldName];
        } elseif (!empty($foreignProperties[$idFieldName])) {
            return $foreignProperties[$idFieldName];
        } else {
            $combinedIdentifier = Record::createCombinedIdentifier($localProperties, $foreignProperties);
            if (strlen($combinedIdentifier) === 0) {
                $filteredLocalProps = array_filter($localProperties);
                $filteredForeignProps = array_filter($foreignProperties);
                if (!empty($filteredLocalProps) && !empty($filteredForeignProps)) {
                    $this->logger->error(
                        'Could not merge identifier values',
                        [
                            'identifierFieldName' => $idFieldName,
                            'tableName' => $tableName,
                            'localProperties' => $localProperties,
                            'foreignProperties' => $foreignProperties,
                        ]
                    );
                }
            } else {
                return $combinedIdentifier;
            }
        }
        return 0;
    }

    /**
     * @param string $instanceTableName
     * @param int $mergedIdentifier
     *
     * @return bool
     */
    protected function isLooping($instanceTableName, $mergedIdentifier): bool
    {
        // loop detection of records waiting for instantiation completion
        if (!empty($this->instantiationQueue[$instanceTableName])
            && in_array($mergedIdentifier, $this->instantiationQueue[$instanceTableName])
        ) {
            $this->logger->info(
                'Recursion detected! This is mostly a sys_file_reference'
                . ' pointing to it\'s sys_file, which gets currently enriched',
                [
                    'instanceTableName' => $instanceTableName,
                    'mergedIdentifier' => $mergedIdentifier,
                ]
            );
            return true;
        }
        if (empty($this->instantiationQueue[$instanceTableName])) {
            $this->instantiationQueue[$instanceTableName] = [];
        }
        $this->instantiationQueue[$instanceTableName][] = $mergedIdentifier;
        return false;
    }

    /**
     * @param string $instanceTableName
     * @param int $mergedIdentifier
     *
     * @return void
     */
    protected function finishedInstantiation($instanceTableName, $mergedIdentifier)
    {
        foreach ($this->instantiationQueue[$instanceTableName] as $index => $identifier) {
            if ($mergedIdentifier === $identifier) {
                unset($this->instantiationQueue[$instanceTableName][$index]);
                break;
            }
        }
    }

    /**
     * public method to get a cached record
     * mainly for performance issues
     *
     * @param string $tableName
     * @param string|int $identifier
     *
     * @return RecordInterface|null
     */
    public function getCachedRecord($tableName, $identifier)
    {
        if (!empty($this->runtimeCache[$tableName][$identifier])) {
            return $this->runtimeCache[$tableName][$identifier];
        }
        return null;
    }

    /**
     * Remove a table/identifier from the runtimeCache to force a re-fetch on a record
     *
     * @param string $tableName
     * @param string $identifier
     */
    public function forgetCachedRecord($tableName, $identifier)
    {
        unset($this->runtimeCache[$tableName][$identifier]);
    }

    /**
     * Disable Page Recursion
     *
     * @return void
     */
    public function disablePageRecursion()
    {
        $this->pageRecursionEnabled = false;
    }

    /**
     * @internal
     */
    public function simulateRootRecord()
    {
        $this->isRootRecord = true;
    }

    /**
     * @internal
     */
    public function endSimulation()
    {
        $this->isRootRecord = false;
        try {
            $this->signalSlotDispatcher->dispatch(
                __CLASS__,
                'rootRecordFinished',
                [$this, GeneralUtility::makeInstance(NullRecord::class)]
            );
        } catch (InvalidSlotException $e) {
        } catch (InvalidSlotReturnException $e) {
        }
    }

    /**
     * @param RecordInterface $record
     * @param CommonRepository $commonRepository
     * @param string|null $forceRelatedRecordTable
     */
    protected function findTranslations(
        RecordInterface $record,
        CommonRepository $commonRepository,
        string $forceRelatedRecordTable = null
    ) {
        $tableName = $record->getTableName();

        $tcaService = GeneralUtility::makeInstance(TcaService::class);

        $languageField = $tcaService->getLanguageField($tableName);
        if (!empty($languageField)) {
            $language = $record->getLocalProperty($languageField);
            if (null === $language) {
                $language = $record->getForeignProperty($languageField);
            }
            if (null !== $language && 0 === (int)$language) {
                $fieldName = $tcaService->getTransOrigPointerField($tableName);
                if ($fieldName) {
                    $translatedRecords = $commonRepository->findByProperties(
                        [$fieldName => $record->getIdentifier()],
                        false,
                        $tableName
                    );
                    foreach ($translatedRecords as $translatedRecord) {
                        $record->addRelatedRecordRaw($translatedRecord, $forceRelatedRecordTable ?? $tableName);
                    }
                }
            }
        }
    }
}
