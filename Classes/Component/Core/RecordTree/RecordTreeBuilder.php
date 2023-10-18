<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\RecordTree;

use In2code\In2publishCore\CommonInjection\EventDispatcherInjection;
use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;
use In2code\In2publishCore\Component\Core\Demand\DemandBuilderInjection;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactoryInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolverInjection;
use In2code\In2publishCore\Component\Core\Record\Factory\RecordFactoryInjection;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordIndexInjection;
use In2code\In2publishCore\Component\Core\Service\RelevantTablesServiceInjection;
use In2code\In2publishCore\Event\RecordRelationsWereResolved;
use In2code\In2publishCore\Service\Configuration\TcaServiceInjection;
use In2code\In2publishCore\Service\Database\RawRecordServiceInjection;

use function array_flip;
use function array_values;
use function in_array;

class RecordTreeBuilder
{
    use ConfigContainerInjection;
    use RecordFactoryInjection;
    use RelevantTablesServiceInjection;
    use RecordIndexInjection;
    use EventDispatcherInjection;
    use DemandsFactoryInjection;
    use DemandResolverInjection;
    use DemandBuilderInjection;
    use TcaServiceInjection;
    use RawRecordServiceInjection;

    public function buildRecordTree(RecordTreeBuildRequest $request): RecordTree
    {
        $recordTree = new RecordTree([], $request);

        $recordCollection = new RecordCollection();

        $defaultIdRequest = $this->setIdToDefaultLanguageId($request);

        $this->findRequestedRecordWithTranslations($defaultIdRequest, $recordTree, $recordCollection);

        $this->findPagesRecursively($defaultIdRequest, $recordCollection);

        $recordCollection = $this->findAllRecordsOnPages();

        $this->findRecordsByTca($recordCollection);

        $this->recordIndex->connectTranslations();

        $this->recordIndex->processDependencies($request->getDependencyRecursionLimit());

        $this->eventDispatcher->dispatch(new RecordRelationsWereResolved($recordTree));

        if ($defaultIdRequest->getId() !== $request->getId()) {
            $record = $recordTree->getChild($request->getTable(), $request->getId());
            $recordTree = new RecordTree([], $request);
            if (null !== $record) {
                $recordTree->addChild($record);
            }
        }

        return $recordTree;
    }

    private function setIdToDefaultLanguageId(RecordTreeBuildRequest $request): RecordTreeBuildRequest
    {
        $table = $request->getTable();
        $id = $request->getId();
        if (0 === $id) {
            return $request;
        }

        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
        $transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] ?? null;
        if (null !== $languageField && null !== $transOrigPointerField) {
            $record = $this->rawRecordService->getRawRecord($table, $id, 'local');
            if (null !== $record && $record[$languageField] > 0) {
                $id = $record[$transOrigPointerField];
                $request = $request->withId($id);
            }
        }
        return $request;
    }

    private function findRequestedRecordWithTranslations(
        RecordTreeBuildRequest $request,
        RecordTree $recordTree,
        RecordCollection $recordCollection
    ): void {
        $table = $request->getTable();
        $id = $request->getId();

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

    private function findPagesRecursively(RecordTreeBuildRequest $request, RecordCollection $recordCollection): void
    {
        $currentRecursion = 0;
        $recursionLimit = $request->getPageRecursionLimit();

        while ($recursionLimit > $currentRecursion++ && !$recordCollection->isEmpty()) {
            $demands = $this->demandsFactory->createDemand();
            $recordsArray = $recordCollection->getRecords('pages');
            if (empty($recordsArray)) {
                break;
            }
            foreach ($recordsArray as $record) {
                $demands->addSelect('pages', '', 'pid', $record->getId(), $record);
            }
            $recordCollection = new RecordCollection();
            $this->demandResolver->resolveDemand($demands, $recordCollection);
        }
    }

    public function findAllRecordsOnPages(): RecordCollection
    {
        // Make a new record collection with all records (pages and subpages or other non-page records).
        // Required for subsequent method calls.
        $recordCollection = new RecordCollection($this->recordIndex->getRecords());

        $pages = $recordCollection->getRecords('pages');
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
