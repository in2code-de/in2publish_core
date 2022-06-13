<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling;

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandService;
use In2code\In2publishCore\Component\TcaHandling\Demand\DemandsFactory;
use In2code\In2publishCore\Component\TcaHandling\Query\QueryService;
use In2code\In2publishCore\Component\TcaHandling\Service\RelevantTablesService;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordTree;
use In2code\In2publishCore\Event\RecordRelationsWereResolved;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

use function array_flip;
use function array_values;

class DerServiceUmbenennen
{
    protected RelevantTablesService $relevantTablesService;
    protected QueryService $queryService;
    protected ConfigContainer $configContainer;
    protected DemandService $demandService;
    protected RecordFactory $recordFactory;
    protected RecordIndex $recordIndex;
    protected EventDispatcher $eventDispatcher;
    protected DemandsFactory $demandsFactory;

    public function injectRelevantTablesService(RelevantTablesService $relevantTablesService): void
    {
        $this->relevantTablesService = $relevantTablesService;
    }

    public function injectQueryService(QueryService $queryService): void
    {
        $this->queryService = $queryService;
    }

    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->configContainer = $configContainer;
    }

    public function injectDemandService(DemandService $demandService): void
    {
        $this->demandService = $demandService;
    }

    public function injectRecordFactory(RecordFactory $recordFactory): void
    {
        $this->recordFactory = $recordFactory;
    }

    public function injectRecordIndex(RecordIndex $recordIndex): void
    {
        $this->recordIndex = $recordIndex;
    }

    public function injectEventDispatcher(EventDispatcher $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function injectDemandsFactory(DemandsFactory $demandsFactory): void
    {
        $this->demandsFactory = $demandsFactory;
    }

    public function buildRecordTree(string $table, int $id): RecordTree
    {
        $recordTree = new RecordTree();

        $records = $this->findRequestedRecordWithTranslations($table, $id, $recordTree);

        $this->findPagesRecursively($records);

        $records = $this->findAllRecordsOnPages();

        $this->findRecordsByTca($records);

        $this->recordIndex->connectTranslations();

        $this->eventDispatcher->dispatch(new RecordRelationsWereResolved());

        return $recordTree;
    }

    private function findRequestedRecordWithTranslations(
        string $table,
        int $id,
        RecordTree $recordTree
    ): RecordCollection {
        if ('pages' === $table && 0 === $id) {
            $pageTreeRootRecord = $this->recordFactory->createPageTreeRootRecord();
            $recordTree->addChild($pageTreeRootRecord);
            return new RecordCollection([$pageTreeRootRecord]);
        }
        $demands = $this->demandsFactory->buildDemand();
        $demands->addSelect($table, '', 'uid', $id, $recordTree);

        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
        if (null !== $transOrigPointerField) {
            $demands->addSelect($table, '', $transOrigPointerField, $id, $recordTree);
        }

        return $this->queryService->resolveDemands($demands);
    }

    /**
     * @param RecordCollection<int, Record> $records
     */
    private function findPagesRecursively(RecordCollection $records): void
    {
        $currentRecursion = 0;
        $recursionLimit = 5;

        while ($recursionLimit > $currentRecursion++ && !empty($records)) {
            $demands = $this->demandsFactory->buildDemand();
            $recordsArray = $records['pages'] ?? [];
            foreach ($recordsArray as $record) {
                $demands->addSelect('pages', '', 'pid', $record->getId(), $record);
            }
            $records = $this->queryService->resolveDemands($demands);
        }
    }

    public function findAllRecordsOnPages(): RecordCollection
    {
        $pages = $this->recordIndex->getRecordByClassification('pages');
        $recordCollection = new RecordCollection($pages);

        if (empty($pages)) {
            return $recordCollection;
        }
        $demands = $this->demandsFactory->buildDemand();

        $tables = $this->relevantTablesService->getAllNonEmptyNonExcludedTcaTables();

        $tablesAsKeys = array_flip(array_values($tables));
        // Do not build demand for pages ("Don't find pages by pid"), because that has been done in findPagesRecursively
        // Skip sys_file and sys_file_metadata because they are not connected to the ID=0, they just don't have a PID.
        unset($tablesAsKeys['pages'], $tablesAsKeys['sys_file'], $tablesAsKeys['sys_file_metadata']);

        $tables = array_flip($tablesAsKeys);

        foreach ($tables as $table) {
            foreach ($pages as $page) {
                $demands->addSelect($table, '', 'pid', $page->getId(), $page);
            }
        }
        $resolvedRecords = $this->queryService->resolveDemands($demands);
        $recordCollection->addRecordCollection($resolvedRecords);
        return $recordCollection;
    }

    /**
     * @param RecordCollection<string, array<int|string, Record>> $recordCollection
     */
    public function findRecordsByTca(RecordCollection $recordCollection): void
    {
        $currentRecursion = 0;
        $recursionLimit = 8;

        while ($recursionLimit > $currentRecursion++ && !$recordCollection->isEmpty()) {
            $demand = $this->demandService->buildDemandForRecords($recordCollection);

            $recordCollection = $this->queryService->resolveDemands($demand);
        }
    }
}
