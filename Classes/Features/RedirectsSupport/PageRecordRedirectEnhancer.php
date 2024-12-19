<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport;

/*
 * Copyright notice
 *
 * (c) 2020 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolverInjection;
use In2code\In2publishCore\Component\Core\Record\Model\Node;
use In2code\In2publishCore\Component\Core\Record\Model\Record;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Event\RecordRelationsWereResolved;
use TYPO3\CMS\Core\LinkHandling\LinkService;

class PageRecordRedirectEnhancer
{
    use DemandResolverInjection;

    protected LinkService $linkService;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function __construct(\TYPO3\CMS\Core\LinkHandling\LinkService $linkService)
    {
        $this->linkService = $linkService;
    }

    public function addRedirectsToPageRecord(RecordRelationsWereResolved $event): void
    {
        $recordTree = $event->getRecordTree();
        $demands = new DemandsCollection();
        $this->collectDemandsForPages($recordTree, $demands);
        $recordCollection = new RecordCollection();
        $this->demandResolver->resolveDemand($demands, $recordCollection);
    }

    protected function collectDemandsForPages(Node $node, DemandsCollection $demands): void
    {
        if ($node instanceof Record) {
            $pid = $node->getId();

            if ($pid < 1 || 'pages' !== $node->getClassification()) {
                return;
            }

            $localProps = $node->getLocalProps();
            if (isset($localProps['sys_language_uid'])) {
                $defaultPageId = (int)$localProps['sys_language_uid'] > 0 ? (int)$localProps['l10n_parent'] : $pid;
                $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
                $language = $localProps[$languageField];
                $targetLink = $this->linkService->asString([
                    'type' => 'page',
                    'pageuid' => $defaultPageId,
                    'parameters' => '_language=' . $language,
                ]);
            } else {
                $targetLink = $this->linkService->asString([
                    'type' => 'page',
                    'pageuid' => $pid,
                ]);
            }

            $demand = new SelectDemand(
                'sys_redirect',
                'tx_in2publishcore_foreign_site_id IS NULL',
                'target',
                $targetLink,
                $node,
            );
            $demands->addDemand($demand);
        }
        foreach ($node->getChildren() as $children) {
            foreach ($children as $child) {
                $this->collectDemandsForPages($child, $demands);
            }
        }
    }
}
