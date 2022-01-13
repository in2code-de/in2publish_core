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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RecordHandling\RecordFinder;
use In2code\In2publishCore\Component\RecordHandling\RecordPublisher;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Controller\AbstractController;
use In2code\In2publishCore\Controller\Traits\ControllerModuleTemplate;
use In2code\In2publishCore\Domain\Service\ExecutionTimeService;
use In2code\In2publishCore\Domain\Service\ForeignSiteFinder;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Dto\Filter;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Repository\SysRedirectRepository;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_column;
use function count;
use function implode;
use function reset;
use function sprintf;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects) I probably really need all these classes.
 */
class RedirectController extends AbstractController
{
    use ControllerModuleTemplate;

    protected ForeignSiteFinder $foreignSiteFinder;

    protected SysRedirectRepository $sysRedirectRepo;

    protected RecordFinder $recordFinder;

    protected RecordPublisher $recordPublisher;

    public function __construct(
        ConfigContainer $configContainer,
        ExecutionTimeService $executionTimeService,
        EnvironmentService $environmentService,
        RemoteCommandDispatcher $remoteCommandDispatcher,
        ForeignSiteFinder $foreignSiteFinder,
        SysRedirectRepository $sysRedirectRepo,
        RecordFinder $recordFinder,
        RecordPublisher $recordPublisher
    ) {
        parent::__construct(
            $configContainer,
            $executionTimeService,
            $environmentService,
            $remoteCommandDispatcher
        );
        $this->foreignSiteFinder = $foreignSiteFinder;
        $this->sysRedirectRepo = $sysRedirectRepo;
        $this->recordFinder = $recordFinder;
        $this->recordPublisher = $recordPublisher;
    }

    /** @throws Throwable */
    public function initializeListAction(): void
    {
        if ($this->request->hasArgument('filter')) {
            $filter = $this->request->getArgument('filter');
            $this->backendUser->setAndSaveSessionData('tx_in2publishcore_redirects_filter', $filter);
        } else {
            $filter = $this->backendUser->getSessionData('tx_in2publishcore_redirects_filter');
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
        $foreignConnection = DatabaseUtility::buildForeignDatabaseConnection();
        $uidList = [];
        if (null !== $foreignConnection) {
            $query = $foreignConnection->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('uid')->from('sys_redirect')->where($query->expr()->eq('deleted', 1));
            $uidList = array_column($query->execute()->fetchAllAssociative(), 'uid');
        }
        $redirects = $this->sysRedirectRepo->findForPublishing($uidList, $filter);
        $paginator = new QueryResultPaginator($redirects, $page, 15);
        $pagination = new SimplePagination($paginator);
        $this->view->assignMultiple(
            [
                'paginator' => $paginator,
                'pagination' => $pagination,
                'hosts' => $this->sysRedirectRepo->findHostsOfRedirects(),
                'statusCodes' => $this->sysRedirectRepo->findStatusCodesOfRedirects(),
                'filter' => $filter,
            ]
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
                AbstractMessage::NOTICE
            );
            $this->redirect('list');
        }

        foreach ($redirects as &$redirect) {
            $redirect = (int)$redirect;
        }
        unset($redirect);

        foreach ($redirects as $redirect) {
            $record = $this->recordFinder->findRecordByUidForPublishing($redirect, 'sys_redirect');
            if (null !== $record) {
                $this->recordPublisher->publishRecordRecursive($record);
            }
        }

        $this->runTasks();
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
     * @throws Throwable
     */
    public function selectSiteAction(int $redirect, array $properties = null): void
    {
        $redirectObj = $this->sysRedirectRepo->findUnrestrictedByIdentifier($redirect);
        if (null === $redirectObj) {
            $this->redirect('list');
        }

        if ($this->request->getMethod() === 'POST') {
            $redirectObj->setSiteId($properties['siteId']);
            $this->sysRedirectRepo->update($redirectObj);
            $this->addFlashMessage(
                sprintf('Associated redirect %s with site %s', $redirectObj->__toString(), $redirectObj->getSiteId())
            );
            if (isset($_POST['_saveandpublish'])) {
                $this->redirect('publish', null, null, ['redirects' => [$redirectObj->getUid()]]);
            }
            $this->redirect('list');
        }
        $sites = $this->foreignSiteFinder->getAllSites();
        $siteOptions = [
            '*' => LocalizationUtility::translate(
                'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_host_global_text'
            ),
        ];
        foreach ($sites as $site) {
            $identifier = $site->getIdentifier();
            $siteOptions[$identifier] = $identifier . ' (' . $site->getBase() . ')';
        }
        $this->view->assign('redirect', $redirectObj);
        $this->view->assign('siteOptions', $siteOptions);
    }
}
