<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\TcaHandling\Demand\DemandBuilder;
use In2code\In2publishCore\Component\TcaHandling\Demand\DemandsFactory;
use In2code\In2publishCore\Component\TcaHandling\DemandResolver\DemandResolver;
use In2code\In2publishCore\Component\TcaHandling\Service\RelevantTablesService;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordTree;
use In2code\In2publishCore\Event\RecordRelationsWereResolved;
use In2code\In2publishCore\Service\Configuration\TcaService;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

use function array_flip;
use function array_values;
use function in_array;

class RecordTreeBuilder
{
    protected RelevantTablesService $relevantTablesService;
    protected DemandResolver $demandResolver;
    protected ConfigContainer $configContainer;
    protected DemandBuilder $demandBuilder;
    protected RecordFactory $recordFactory;
    protected RecordIndex $recordIndex;
    protected EventDispatcher $eventDispatcher;
    protected DemandsFactory $demandsFactory;
    protected TcaService $tcaService;

    public function injectRelevantTablesService(RelevantTablesService $relevantTablesService): void
    {
        $this->relevantTablesService = $relevantTablesService;
    }

    public function injectDemandResolverFactory(DemandResolver $demandResolver): void
    {
        $this->demandResolver = $demandResolver;
    }

    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->configContainer = $configContainer;
    }

    public function injectDemandBuilder(DemandBuilder $demandBuilder): void
    {
        $this->demandBuilder = $demandBuilder;
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

    public function injectTcaService(TcaService $tcaService): void
    {
        $this->tcaService = $tcaService;
    }

    public function buildRecordTree(string $table, int $id): RecordTree
    {
        $recordTree = new RecordTree();

        $recordCollection = new RecordCollection();

        $this->findRequestedRecordWithTranslations($table, $id, $recordTree, $recordCollection);

        $this->findPagesRecursively($recordCollection, $recordCollection);

        $recordCollection = $this->findAllRecordsOnPages();

        $this->findRecordsByTca($recordCollection);

        $this->recordIndex->connectTranslations();

        $this->eventDispatcher->dispatch(new RecordRelationsWereResolved($recordTree));

        return $recordTree;
    }

    private function findRequestedRecordWithTranslations(
        string $table,
        int $id,
        RecordTree $recordTree,
        RecordCollection $recordCollection
    ): void {
        if ('pages' === $table && 0 === $id) {
            $pageTreeRootRecord = $this->recordFactory->createPageTreeRootRecord();
            $recordTree->addChild($pageTreeRootRecord);
            $recordCollection->addRecord($pageTreeRootRecord);
            return;
        }
        $demands = $this->demandsFactory->createDemand();
        $demands->addSelect($table, '', 'uid', $id, $recordTree);

        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
        if (null !== $transOrigPointerField) {
            $demands->addSelect($table, '', $transOrigPointerField, $id, $recordTree);
        }

        $this->demandResolver->resolveDemand($demands, $recordCollection);
    }

    /**
     * @param RecordCollection<int, Record> $records
     */
    private function findPagesRecursively(RecordCollection $records, RecordCollection $recordCollection): void
    {
        $currentRecursion = 0;
        $recursionLimit = 5;

        while ($recursionLimit > $currentRecursion++ && !$recordCollection->isEmpty()) {
            $demands = $this->demandsFactory->createDemand();
            $recordsArray = $records->getRecords('pages');
            foreach ($recordsArray as $record) {
                $demands->addSelect('pages', '', 'pid', $record->getId(), $record);
            }
            $recordCollection = new RecordCollection();
            $this->demandResolver->resolveDemand($demands, $recordCollection);
        }
    }

    public function findAllRecordsOnPages(): RecordCollection
    {
        $pages = $this->recordIndex->getRecords('pages');
        $recordCollection = new RecordCollection($pages);

        if ($recordCollection->isEmpty()) {
            return $recordCollection;
        }
        $demands = $this->demandsFactory->createDemand();

        $tables = $this->relevantTablesService->getAllNonEmptyNonExcludedTcaTables();

        $tablesAsKeys = array_flip(array_values($tables));
        // Do not build demand for pages ("Don't find pages by pid"), because that has been done in findPagesRecursively
        // Skip sys_file and sys_file_metadata because they are not connected to the ID=0, they just don't have a PID.
        unset($tablesAsKeys['pages'], $tablesAsKeys['sys_file'], $tablesAsKeys['sys_file_metadata']);

        $tables = array_flip($tablesAsKeys);

        foreach ($pages as $page) {
            $tablesAllowedOnPage = $this->tcaService->getTablesAllowedOnPage(
                $page->getId(),
                $page->getProp('doktype')
            );
            foreach ($tables as $table) {
                if (in_array($table, $tablesAllowedOnPage)) {
                    $demands->addSelect($table, '', 'pid', $page->getId(), $page);
                }
            }
        }
        $this->demandResolver->resolveDemand($demands, $recordCollection);
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
            $demand = $this->demandBuilder->buildDemandForRecords($recordCollection);

            $recordCollection = new RecordCollection();
            $this->demandResolver->resolveDemand($demand, $recordCollection);
        }
    }
}
