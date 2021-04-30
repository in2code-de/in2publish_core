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

use In2code\In2publishCore\Controller\AbstractController;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Domain\Service\ForeignSiteFinder;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\SysRedirect;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Repository\SysRedirectRepository;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class RedirectController extends AbstractController
{
    protected const ALLOWED_FIELDS = [
        'pageUid',
    ];
    private const ALLOWED_DIRECTIONS = ['ASC', 'DESC'];

    /** @var SysRedirectRepository */
    protected $sysRedirectRepo;

    public function injectSysRedirectRepository(SysRedirectRepository $sysRedirectRepo): void
    {
        $this->sysRedirectRepo = $sysRedirectRepo;
    }

    public function listAction(): void
    {
        $foreignConnection = DatabaseUtility::buildForeignDatabaseConnection();
        $uidList = [];
        if (null !== $foreignConnection) {
            $query = $foreignConnection->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->select('uid')->from('sys_redirect')->where($query->expr()->eq('deleted', 1));
            $uidList = array_column($query->execute()->fetchAll(), 'uid');
        }

        $query = $this->sysRedirectRepo->createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setIgnoreEnableFields(true);
        $querySettings->setRespectSysLanguage(false);
        $querySettings->setRespectStoragePage(false);
        $querySettings->setIncludeDeleted(true);
        $query->matching(
            $query->logicalOr(
                [
                    $query->equals('deleted', 0),
                    $query->logicalNot($query->in('uid', $uidList)),
                ]
            )
        );
        $redirects = $query->execute();
        $this->view->assign('redirects', $redirects);
    }

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

        $commonRepository = CommonRepository::getDefaultInstance();
        $records = $commonRepository->findByProperty('uid', $redirects, 'sys_redirect');
        foreach ($records as $record) {
            $commonRepository->publishRecordRecursive($record);
        }
        $this->runTasks();
        if (count($redirects) === 1) {
            $this->addFlashMessage(sprintf('Redirect %s published', reset($redirects)));
        } else {
            $this->addFlashMessage(sprintf('Redirects %s published', implode(', ', $redirects)));
        }
        $this->redirect('list');
    }

    public function selectSiteAction(SysRedirect $redirect): void
    {
        if ($this->request->getMethod() === 'POST') {
            $this->sysRedirectRepo->update($redirect);
            $this->addFlashMessage(sprintf('Associated redirect %s with site %s', $redirect, $redirect->getSiteId()));
            if (isset($_POST['_saveandpublish'])) {
                $this->redirect('publish', null, null, ['redirects' => [$redirect->getUid()]]);
            }
            $this->redirect('list');
        }
        $siteFinder = GeneralUtility::makeInstance(ForeignSiteFinder::class);
        $sites = $siteFinder->getAllSites();
        $siteOptions = [
            '*' => LocalizationUtility::translate(
                'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_host_global_text'
            ),
        ];
        foreach ($sites as $site) {
            $identifier = $site->getIdentifier();
            $siteOptions[$identifier] = $identifier . ' (' . $site->getBase() . ')';
        }
        $this->view->assign('redirect', $redirect);
        $this->view->assign('siteOptions', $siteOptions);
    }
}
