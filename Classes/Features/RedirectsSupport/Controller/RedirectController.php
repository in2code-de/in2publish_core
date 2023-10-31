<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Controller;

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

use In2code\In2publishCore\CommonInjection\ForeignDatabaseInjection;
use In2code\In2publishCore\CommonInjection\IconFactoryInjection;
use In2code\In2publishCore\CommonInjection\PageRendererInjection;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactoryInjection;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\SysRedirectDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\DemandResolverInjection;
use In2code\In2publishCore\Component\Core\Publisher\PublisherServiceInjection;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Controller\Traits\ControllerModuleTemplate;
use In2code\In2publishCore\Features\RedirectsSupport\Backend\Button\BackButton;
use In2code\In2publishCore\Features\RedirectsSupport\Backend\Button\SaveAndPublishButton;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Dto\Filter;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Repository\SysRedirectRepository;
use In2code\In2publishCore\Service\ForeignSiteFinderInjection;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_column;
use function count;
use function implode;
use function reset;
use function sprintf;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) I probably really need all these classes.
 */
class RedirectController extends ActionController
{
    use ControllerModuleTemplate;
    use IconFactoryInjection;
    use DemandsFactoryInjection;
    use DemandResolverInjection;
    use ForeignSiteFinderInjection;
    use PageRendererInjection {
        injectPageRenderer as actualInjectPageRenderer;
    }
    use PublisherServiceInjection;
    use ForeignDatabaseInjection;

    protected SysRedirectRepository $sysRedirectRepo;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectSysRedirectRepo(SysRedirectRepository $sysRedirectRepo): void
    {
        $this->sysRedirectRepo = $sysRedirectRepo;
    }

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->actualInjectPageRenderer($pageRenderer);
        $this->pageRenderer->addCssFile(
            'EXT:in2publish_core/Resources/Public/Css/Modules.css',
            'stylesheet',
            'all',
            '',
            false,
        );
    }

    /** @throws Throwable */
    public function initializeListAction(): void
    {
        if ($this->request->hasArgument('filter')) {
            $filter = $this->request->getArgument('filter');
            $GLOBALS['BE_USER']->setAndSaveSessionData('tx_in2publishcore_redirects_filter', $filter);
        } else {
            $filter = $GLOBALS['BE_USER']->getSessionData('tx_in2publishcore_redirects_filter');
            if (null !== $filter) {
                $this->request->setArgument('filter', $filter);
            }
            $this->arguments->getArgument('filter')->getPropertyMappingConfiguration()->allowAllProperties();
        }
    }

    /**
     * @param Filter|null $filter
     * @param int $page
     * @return ResponseInterface
     * @throws Throwable
     */
    public function listAction(Filter $filter = null, int $page = 1): ResponseInterface
    {
        $query = $this->foreignDatabase->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('uid')->from('sys_redirect')->where($query->expr()->eq('deleted', 1));
        $foreignDeletedRedirects = $query->execute()->fetchAll();
        $additionalWhere = '';
        if (!empty($foreignDeletedRedirects)) {
            $uidList = implode(',', array_column($foreignDeletedRedirects, 'uid'));

            $additionalWhere = "deleted = 0 OR uid NOT IN ($uidList)";
            if (null !== $filter) {
                $additionalWhere = $filter->toAdditionWhere();
            }
        }

        $recordTree = new RecordTree();

        $demands = $this->demandsFactory->createDemand();
        $demands->addDemand(new SysRedirectDemand('sys_redirect', $additionalWhere, $recordTree));

        $recordCollection = new RecordCollection();
        $this->demandResolver->resolveDemand($demands, $recordCollection);

        $redirects = $recordTree->getChildren()['sys_redirect'] ?? [];
        $paginator = new ArrayPaginator($redirects, $page, 15);
        $pagination = new SimplePagination($paginator);
        $this->view->assignMultiple(
            [
                'paginator' => $paginator,
                'pagination' => $pagination,
                'hosts' => $this->sysRedirectRepo->findHostsOfRedirects(),
                'statusCodes' => $this->sysRedirectRepo->findStatusCodesOfRedirects(),
                'filter' => $filter,
            ],
        );
        return $this->htmlResponse();
    }

    /** @throws Throwable */
    public function publishAction(array $redirects): void
    {
        if (empty($redirects)) {
            $this->addFlashMessage(
                'No redirect has been selected for publishing',
                'Skipping publishing',
                AbstractMessage::NOTICE,
            );
            $this->redirect('list');
        }

        $recordTree = new RecordTree();

        $demands = $this->demandsFactory->createDemand();
        foreach ($redirects as $redirect) {
            $demands->addDemand(new SelectDemand('sys_redirect', '', 'uid', $redirect, $recordTree));
        }

        $recordCollection = new RecordCollection();
        $this->demandResolver->resolveDemand($demands, $recordCollection);

        $this->publisherService->publishRecordTree($recordTree);

        if (count($redirects) === 1) {
            $this->addFlashMessage(sprintf('Redirect %s published', reset($redirects)));
        } else {
            $this->addFlashMessage(sprintf('Redirects %s published', implode(', ', $redirects)));
        }
        $this->redirect('list');
    }

    /**
     * @param int $redirect
     * @param array|null $properties
     * @return ResponseInterface
     * @throws Throwable
     */
    public function selectSiteAction(int $redirect, array $properties = null): ResponseInterface
    {
        $redirectDto = $this->sysRedirectRepo->findLocalRawByUid($redirect);
        if (null === $redirectDto) {
            $this->redirect('list');
        }

        if ($this->request->getMethod() === 'POST') {
            $redirectDto->tx_in2publishcore_foreign_site_id = $properties['siteId'];
            $this->sysRedirectRepo->update($redirectDto);
            $this->addFlashMessage(
                sprintf(
                    'Associated redirect %s with site %s',
                    $redirectDto->__toString(),
                    $redirectDto->tx_in2publishcore_foreign_site_id,
                ),
            );
            if (isset($_POST['_saveandpublish'])) {
                $this->redirect('publish', null, null, ['redirects' => [$redirectDto->uid]]);
            }
            $this->redirect('list');
        }
        $sites = $this->foreignSiteFinder->getAllSites();
        $siteOptions = [
            '*' => LocalizationUtility::translate(
                'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_host_global_text',
            ),
        ];
        foreach ($sites as $site) {
            $identifier = $site->getIdentifier();
            $siteOptions[$identifier] = $identifier . ' (' . $site->getBase() . ')';
        }
        $this->view->assign('redirect', $redirectDto);
        $this->view->assign('siteOptions', $siteOptions);

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $buttonBar->addButton(new BackButton($this->iconFactory, $this->uriBuilder));
        $buttonBar->addButton(new SaveAndPublishButton($this->iconFactory));

        return $this->htmlResponse();
    }
}
