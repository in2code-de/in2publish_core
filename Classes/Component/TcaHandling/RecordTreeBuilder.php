<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling;

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandBuilder;
use In2code\In2publishCore\Component\TcaHandling\Demand\DemandsFactory;
use In2code\In2publishCore\Component\TcaHandling\Demand\Resolver\DemandResolverCollection;
use In2code\In2publishCore\Component\TcaHandling\Demand\Resolver\JoinDemandResolver;
use In2code\In2publishCore\Component\TcaHandling\Demand\Resolver\SelectDemandResolver;
use In2code\In2publishCore\Component\TcaHandling\Demand\Resolver\SysRedirectSelectDemandResolver;
use In2code\In2publishCore\Component\TcaHandling\Service\RelevantTablesService;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordTree;
use In2code\In2publishCore\Event\RecordRelationsWereResolved;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;

use function array_flip;
use function array_values;

class RecordTreeBuilder
{
    protected RelevantTablesService $relevantTablesService;
    protected SelectDemandResolver $selectDemandResolver;
    protected JoinDemandResolver $joinDemandResolver;
    protected SysRedirectSelectDemandResolver $sysRedirectSelectDemandResolver;
    protected DemandResolverCollection $demandResolverCollection;
    protected ConfigContainer $configContainer;
    protected DemandBuilder $demandBuilder;
    protected RecordFactory $recordFactory;
    protected RecordIndex $recordIndex;
    protected EventDispatcher $eventDispatcher;
    protected DemandsFactory $demandsFactory;

    public function injectRelevantTablesService(RelevantTablesService $relevantTablesService): void
    {
        $this->relevantTablesService = $relevantTablesService;
    }

    public function injectSelectDemandResolver(SelectDemandResolver $selectDemandResolver): void
    {
        $this->selectDemandResolver = $selectDemandResolver;
    }

    public function injectJoinDemandResolver(JoinDemandResolver $joinDemandResolver): void
    {
        $this->joinDemandResolver = $joinDemandResolver;
    }

    public function injectSysRedirectSelectDemandResolver(SysRedirectSelectDemandResolver $sysRedirectSelectDemandResolver): void
    {
        $this->sysRedirectSelectDemandResolver = $sysRedirectSelectDemandResolver;
    }

    public function injectDemandResolverCollection(DemandResolverCollection $demandResolverCollection): void
    {
        $this->demandResolverCollection = $demandResolverCollection;
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

    public function buildRecordTree(string $table, int $id): RecordTree
    {
        $this->demandResolverCollection->addDemandResolver($this->selectDemandResolver);
        $this->demandResolverCollection->addDemandResolver($this->joinDemandResolver);
        $this->demandResolverCollection->addDemandResolver($this->sysRedirectSelectDemandResolver);

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

        $this->demandResolverCollection->resolveDemand($demands, $recordCollection);
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
            $recordsArray = $records['pages'] ?? [];
            foreach ($recordsArray as $record) {
                $demands->addSelect('pages', '', 'pid', $record->getId(), $record);
            }
            $recordCollection = new RecordCollection();
            $this->demandResolverCollection->resolveDemand($demands, $recordCollection);
        }
    }

    public function findAllRecordsOnPages(): RecordCollection
    {
        $pages = $this->recordIndex->getRecordByClassification('pages');
        $recordCollection = new RecordCollection($pages);

        if (empty($pages)) {
            return $recordCollection;
        }
        $demands = $this->demandsFactory->createDemand();

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
        $this->selectDemandResolver->resolveDemand($demands, $recordCollection);
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
            $this->selectDemandResolver->resolveDemand($demand, $recordCollection);
        }
    }
}
