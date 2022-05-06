<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Factory;

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

use In2code\In2publishCore\Component\RecordHandling\DefaultRecordFinder;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\NullRecord;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\Exception\MissingArgumentException;
use In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord;
use In2code\In2publishCore\Event\RecordInstanceWasInstantiated;
use In2code\In2publishCore\Event\RootRecordCreationWasFinished;
use In2code\In2publishCore\Service\Configuration\TcaService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_diff;
use function array_filter;
use function array_merge;
use function in_array;
use function max;

/**
 * RecordFactory: This class is responsible for create instances of Record.
 * This class is called recursively for the record, any related
 * records and any of the related records related records and so on to the extend
 * of the setting maximumRecursionDepth
 */
class RecordFactory implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var TcaService */
    protected $tcaService;

    /** @var EventDispatcher */
    protected $eventDispatcher;

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
     * @var array
     */
    protected $config = [
        'maximumPageRecursion' => 0,
        'maximumContentRecursion' => 0,
        'maximumOverallRecursion' => 0,
        'resolvePageRelations' => true,
        'includeSysFileReference' => false,
    ];

    /** @var int current recursion depth of makeInstance */
    protected $currentDepth = 0;

    /**
     * array of table names to be excluded from publishing
     * currently only used for related records of "page records"
     *
     * @var array
     */
    protected $excludedTableNames = [];

    /** @var int current depth of related page records */
    protected $pagesDepth = 1;

    /** @var int current depth of related content records (anything but page) */
    protected $relatedRecordsDepth = 1;

    /** @var bool */
    protected $pageRecursionEnabled = true;

    /**  @var bool */
    protected $isRootRecord = false;

    public function __construct(
        ConfigContainer $configContainer,
        TcaService $tcaService,
        EventDispatcher $eventDispatcher
    ) {
        $this->tcaService = $tcaService;
        $this->eventDispatcher = $eventDispatcher;

        $this->config = $configContainer->get('factory');

        $this->config['maximumOverallRecursion'] = max(
            $this->config['maximumOverallRecursion'],
            $this->config['maximumPageRecursion'] + $this->config['maximumContentRecursion']
        );

        $this->excludedTableNames = $configContainer->get('excludeRelatedTables');
    }

    /**
     * Creates a fresh instance of a record and sets all related Records.
     *
     * @param DefaultRecordFinder $commonRecordFinder Needed for recursion
     * @param array $localProperties Properties of the record from local Database
     * @param array $foreignProperties Properties of the record from foreign Database
     * @param array $additionalProperties array of not persisted properties
     * @param string|null $tableName
     * @param string $idFieldName
     * @param array<string>|null $idFields
     *
     * @return RecordInterface|null
     */
    public function makeInstance(
        DefaultRecordFinder $commonRecordFinder,
        array $localProperties,
        array $foreignProperties,
        array $additionalProperties = [],
        string $tableName = null,
        string $idFieldName = 'uid',
        array $idFields = null
    ): ?RecordInterface {
        if (null === $tableName) {
            throw new MissingArgumentException('tableName');
        }

        if (false === $this->isRootRecord) {
            $this->isRootRecord = true;
            $isRootRecord = true;
        } else {
            $isRootRecord = false;
        }
        // one of the property arrays might be empty,
        // to get the identifier we have to take a look into both arrays
        $mergedIdentifier = $this->getMergedIdentifierValue(
            $localProperties,
            $foreignProperties,
            $tableName,
            $idFieldName,
            $idFields
        );

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
        if (
            ($tableName === 'pages' || $mergedIdentifier > 0)
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
                $additionalProperties,
                $mergedIdentifier
            );
            if (true === $isRootRecord && true === $this->isRootRecord) {
                $instance->addAdditionalProperty('isRoot', true);
            }

            if (
                $instance->getIdentifier() !== 0
                && !$instance->localRecordExists()
                && !$instance->foreignRecordExists()
            ) {
                return $instance;
            }

            if ($hasBeenMoved && !$instance->isChanged()) {
                $instance->setState(RecordInterface::RECORD_STATE_MOVED);
            }

            $this->eventDispatcher->dispatch(new RecordInstanceWasInstantiated($this, $instance));

            /* special case of tables without TCA (currently only sys_file_processedfile).
             * Normally we would just ignore them, but:
             *      sys_file_processedfile:
             *          needed for RTE magic images - relation to original image
             */
            $tableConfiguration = $this->tcaService->getConfigurationArrayForTable($tableName);
            if (empty($tableConfiguration)) {
                if ('sys_file_processedfile' === $tableName) {
                    $identifier = null;
                    if ($instance->localRecordExists()) {
                        $identifier = $instance->getLocalProperty('original');
                    }
                    if (empty($identifier) && $instance->foreignRecordExists()) {
                        $identifier = $instance->getForeignProperty('original');
                    }
                    if (!empty($identifier)) {
                        $record = $commonRecordFinder->findByIdentifier($identifier, 'sys_file');
                        $instance->addRelatedRecord($record);
                    }
                }
            } elseif ($this->currentDepth < $this->config['maximumOverallRecursion']) {
                $this->currentDepth++;
                if ($tableName === 'pages') {
                    $instance = $this->findRelatedRecordsForPageRecord($instance, $commonRecordFinder);
                } else {
                    $instance = $this->findRelatedRecordsForContentRecord($instance, $commonRecordFinder);
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
            $this->eventDispatcher->dispatch(new RootRecordCreationWasFinished($this, $instance));
        }
        return $instance;
    }

    protected function findRelatedRecordsForContentRecord(
        RecordInterface $record,
        DefaultRecordFinder $commonRecordFinder
    ): RecordInterface {
        if ($this->relatedRecordsDepth < $this->config['maximumContentRecursion']) {
            $this->relatedRecordsDepth++;
            $excludedTableNames = $this->excludedTableNames;
            if (false === $this->config['resolvePageRelations']) {
                $excludedTableNames[] = 'pages';
            }

            $this->findTranslations($record, $commonRecordFinder);

            $record = $commonRecordFinder->enrichRecordWithRelatedRecords($record, $excludedTableNames);

            $this->eventDispatcher->dispatch(new AllRelatedRecordsWereAddedToOneRecord($this, $record));

            $this->relatedRecordsDepth--;
        }
        return $record;
    }

    protected function findRelatedRecordsForPageRecord(
        Record $record,
        DefaultRecordFinder $commonRecordFinder
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
            $record = $commonRecordFinder->enrichPageRecord($record, $tableNamesToExclude);
            $this->pagesDepth--;
        } else {
            // get related records without table pages
            $tableNamesToExclude[] = 'pages';
            $record = $commonRecordFinder->enrichPageRecord($record, $tableNamesToExclude);
        }
        $relatedRecordsDepth = $this->relatedRecordsDepth;
        $this->relatedRecordsDepth = 0;
        $record = $this->findRelatedRecordsForContentRecord($record, $commonRecordFinder);
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
     * @param string|int $identifier
     * @param array $localProperties
     * @param array $foreignProperties
     *
     * @return bool
     */
    protected function detectAndAlterMovedInstance(
        string $tableName,
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
            } elseif (
                ($sortField = ($GLOBALS['TCA'][$tableName]['ctrl']['sortby'] ?? null))
                && ($localProperties[$sortField] ?? null) !== ($foreignProperties[$sortField] ?? null)
            ) {
                $hasBeenMoved = true;
            }
        }
        return $hasBeenMoved;
    }

    /**
     * gets the field name of the identifier field and
     * checks both arrays for an exiting identity value
     *
     * @param array $localProperties
     * @param array $foreignProperties
     * @param string|null $tableName
     * @param string $idFieldName
     * @param array<string>|null $idFields
     *
     * @return int|string
     */
    protected function getMergedIdentifierValue(
        array $localProperties,
        array $foreignProperties,
        string $tableName,
        string $idFieldName = 'uid',
        array $idFields = null
    ) {
        if ($tableName === 'sys_file') {
            $idFieldName = 'uid';
        }
        if (!empty($localProperties[$idFieldName])) {
            return (int)$localProperties[$idFieldName];
        }

        if (!empty($foreignProperties[$idFieldName])) {
            return (int)$foreignProperties[$idFieldName];
        }

        $combinedIdentifier = Record::createCombinedIdentifier($localProperties, $foreignProperties, $idFields);

        if ($combinedIdentifier !== '') {
            return $combinedIdentifier;
        }

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
        return 0;
    }

    /**
     * @param string $instanceTableName
     * @param string|int $mergedIdentifier
     *
     * @return bool
     */
    protected function isLooping(string $instanceTableName, $mergedIdentifier): bool
    {
        // loop detection of records waiting for instantiation completion
        if (
            !empty($this->instantiationQueue[$instanceTableName])
            && in_array($mergedIdentifier, $this->instantiationQueue[$instanceTableName])
        ) {
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
     * @param int|string $mergedIdentifier
     *
     * @return void
     */
    protected function finishedInstantiation(string $instanceTableName, $mergedIdentifier): void
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
    public function getCachedRecord(string $tableName, $identifier): ?RecordInterface
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
     * @param int|string $identifier
     */
    public function forgetCachedRecord(string $tableName, $identifier): void
    {
        unset($this->runtimeCache[$tableName][$identifier]);
    }

    public function disablePageRecursion(): void
    {
        $this->pageRecursionEnabled = false;
    }

    /**
     * @internal
     */
    public function simulateRootRecord(): void
    {
        $this->isRootRecord = true;
    }

    /**
     * @internal
     */
    public function endSimulation(): void
    {
        $this->isRootRecord = false;
        $record = GeneralUtility::makeInstance(NullRecord::class);
        $this->eventDispatcher->dispatch(new RootRecordCreationWasFinished($this, $record));
    }

    protected function findTranslations(RecordInterface $record, DefaultRecordFinder $commonRecordFinder): void
    {
        $tableName = $record->getTableName();

        $languageField = $this->tcaService->getLanguageField($tableName);
        if (!empty($languageField)) {
            $language = $record->getLocalProperty($languageField);
            if (null === $language) {
                $language = $record->getForeignProperty($languageField);
            }
            if (null !== $language && 0 === (int)$language) {
                $fieldName = $this->tcaService->getTransOrigPointerField($tableName);
                if ($fieldName) {
                    $translatedRecords = $commonRecordFinder->findByProperties(
                        [$fieldName => $record->getIdentifier()],
                        false,
                        $tableName
                    );
                    foreach ($translatedRecords as $translatedRecord) {
                        $translatedRecord->setParentRecord($record);
                        $record->addTranslatedRecord($translatedRecord);
                    }
                }
            }
        }
    }
}
