<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling;

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandService;
use In2code\In2publishCore\Component\TcaHandling\Query\QueryService;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Domain\Model\Record;

use function array_diff;
use function array_keys;
use function array_merge;
use function implode;
use function preg_match_all;

class DerServiceUmbenennen
{
    protected QueryService $queryService;

    protected ConfigContainer $configContainer;

    protected DemandService $demandService;
    protected RecordFactory $recordFactory;

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

    public function neueMagie(string $table, int $id): Record
    {
        $rootRecord =$this->recordFactory->createRootRecord();


        $demand = [];
        $demand['select'][$table]['']['uid'][$id] = $rootRecord;

        $currentRecursion = 0;
        $recursionLimit = 5;

        $allRecords = new TempRecordIndex();

        $newRecords = [];
        /** @var Record $initialRecord */
        $newRecords[] = $initialRecord = $this->queryService->resolveDemand($demand, $allRecords)[0];
        $allRecords->addRecord($initialRecord);

        // Find all translations of the first page.
        // They have the same PID as the first page, so they will not be found in the rootline.
        $transOrigPointerField = $GLOBALS['TCA'][$initialRecord->getClassification()]['ctrl']['transOrigPointerField']
                                 ??
                                 null;
        if (null !== $transOrigPointerField) {
            $demand = [];
            $demand['select'][$initialRecord->getClassification()][''][$transOrigPointerField][$initialRecord->getId(
            )] = $rootRecord;
            $initialRecordTranslations = $this->queryService->resolveDemand($demand, $allRecords);
            $allRecords->addRecords($initialRecordTranslations);
            foreach ($initialRecordTranslations as $pageTranslation) {
                $newRecords[] = $pageTranslation;
            }
        }

        if ($initialRecord->getClassification() === 'pages') {
            while ($recursionLimit > $currentRecursion++ && !empty($newRecords)) {
                $demand = [];
                foreach ($newRecords as $newRecord) {
                    $demand['select']['pages']['']['pid'][$newRecord->getId()] = $newRecord;
                }
                $newRecords = $this->queryService->resolveDemand($demand, $allRecords);
                $allRecords->addRecords($newRecords);
            }
        }

        $pages = $allRecords->getRecordByClassification('pages');
        if (!empty($pages)) {
            $demand = [];
            $excludeRelatedTables = $this->configContainer->get('excludeRelatedTables');

            $regex = '/,(' . implode('|', array_merge(['pages'], $excludeRelatedTables)) . '),/iU';
            $tables = array_keys($GLOBALS['TCA']);
            $tablesString = ',' . implode(',,', $tables) . ',';
            $matches = [];
            preg_match_all($regex, $tablesString, $matches);
            $nonExcludedTables = array_diff($tables, $matches[1]);

            foreach ($nonExcludedTables as $table) {
                foreach ($pages as $page) {
                    $demand['select'][$table]['']['pid'][$page->getId()] = $page;
                }
            }
            $newRecords = $this->queryService->resolveDemand($demand, $allRecords);
            $allRecords->addRecords($newRecords);
            foreach ($pages as $page) {
                $newRecords[] = $page;
            }
        }

        $currentRecursion = 0;
        $recursionLimit = 5;

        while ($recursionLimit > $currentRecursion++ && !empty($newRecords)) {
            $demand = $this->demandService->buildDemandForRecords($newRecords);

            $newRecords = $this->queryService->resolveDemand($demand, $allRecords);
            $allRecords->addRecords($newRecords);
        }

        $allRecords->connectTranslations();

        return $rootRecord;
    }
}
