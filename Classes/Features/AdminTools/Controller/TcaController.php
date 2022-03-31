<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\AdminTools\Controller;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandService;
use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessingService;
use In2code\In2publishCore\Component\TcaHandling\Query\QueryService;
use In2code\In2publishCore\Domain\Model\DatabaseRecord;
use In2code\In2publishCore\Features\AdminTools\Controller\Traits\AdminToolsModuleTemplate;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

use function array_keys;

class TcaController extends ActionController
{
    use AdminToolsModuleTemplate;

    protected TcaPreProcessingService $tcaPreProcessingService;

    protected DemandService $demandService;

    protected QueryService $queryService;

    public function __construct(TcaPreProcessingService $tcaPreProcessingService)
    {
        $this->tcaPreProcessingService = $tcaPreProcessingService;
    }

    public function injectDemandService(DemandService $demandService): void
    {
        $this->demandService = $demandService;
    }

    public function injectQueryService(QueryService $queryService): void
    {
        $this->queryService = $queryService;
    }

    public function indexAction(): ResponseInterface
    {
        $this->view->assign('incompatibleTca', $this->tcaPreProcessingService->getIncompatibleTcaParts());
        $compatibleTcaParts = $this->tcaPreProcessingService->getCompatibleTcaParts();
        $this->view->assign('compatibleTca', $compatibleTcaParts);

        $rootRecord = new DatabaseRecord('pages', 0, [], []);

        $demand = [];
        $demand['select']['pages']['']['uid'][1] = $rootRecord;

        $currentRecursion = 0;
        $recursionLimit = 0;

        $newRecords = [];
        $pages = [];
        $newRecords[] = $pages[] = $page = $this->queryService->resolveDemand($demand)[0];

        // Find all translations of the first page.
        // They have the same PID as the first page, so they will not be found in the rootline.
        $transOrigPointerField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
        $demand = [];
        $demand['select']['pages'][''][$transOrigPointerField][$page->getId()] = $page;
        $newRecords[] = $pages[] = $this->queryService->resolveDemand($demand)[0];

        while ($recursionLimit > $currentRecursion++) {
            $demand = [];
            foreach ($newRecords as $newRecord) {
                $demand['select']['pages']['']['pid'][$newRecord->getId()] = $newRecord;
            }
            $newRecords = $this->queryService->resolveDemand($demand);
            foreach ($newRecords as $newRecord) {
                $pages[] = $newRecord;
            }
        }

        $demand = [];
        $TCA = $GLOBALS['TCA'];
        unset($TCA['pages']);
        foreach (array_keys($TCA) as $table) {
            foreach ($pages as $page) {
                $demand['select'][$table]['']['pid'][$page->getId()] = $page;
            }
        }
        $newRecords = $this->queryService->resolveDemand($demand);
        foreach ($pages as $page) {
            $newRecords[] = $page;
        }

        $currentRecursion = 0;
        $recursionLimit = 5;

        while ($recursionLimit > $currentRecursion++) {
            $demand = $this->demandService->buildDemandForRecords($newRecords);

            $newRecords = $this->queryService->resolveDemand($demand);
            if (empty($newRecords)) {
                break;
            }
        }

        return $this->htmlResponse();
    }
}
