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
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Model\SysRedirect;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Repository\SysRedirectRepository;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RedirectController extends AbstractController
{
    protected const ALLOWED_FIELDS = [
        'pageUid'
    ];
    private const ALLOWED_DIRECTIONS = ['ASC', 'DESC'];

    /** @var SysRedirectRepository */
    protected $sysRedirectRepository;

    public function injectSysRedirectRepository(SysRedirectRepository $sysRedirectRepository): void
    {
        $this->sysRedirectRepository = $sysRedirectRepository;
    }

    public function listAction(array $orderBy = null): void
    {
        $query = $this->sysRedirectRepository->createQuery();
        if (null !== $orderBy) {
            foreach ($orderBy as $field => $direction) {
                if (!in_array($field, self::ALLOWED_FIELDS) || !in_array($direction, self::ALLOWED_DIRECTIONS)) {
                    $this->addFlashMessage(
                        'Ingoring order by ' . $field . ' because it is not allowed',
                        '',
                        AbstractMessage::ERROR
                    );
                    unset($orderBy[$field]);
                }
                $query->setOrderings($orderBy);
            }
        }
        $redirects = $query->execute();
        $this->view->assign('redirects', $redirects);
        $this->view->assign('orderBy', $orderBy);
    }

    public function editAction(SysRedirect $redirect): void
    {
        if ($this->request->getMethod() === 'POST') {
            $this->sysRedirectRepository->update($redirect);
            $this->addFlashMessage(sprintf('Associated redirect %s with page %d', $redirect, $redirect->getPageUid()));
            if (isset($_POST['_saveandclose'])) {
                $this->redirect('list');
            }
            $this->redirect('edit', null, null, ['redirect' => $redirect]);
        }
        GeneralUtility::makeInstance(FormResultCompiler::class)->printNeededJSFunctions();
        $this->view->assign('redirect', $redirect);
    }

    public function publishAction(array $redirects): void
    {
        if (empty($redirects)) {
            $this->addFlashMessage('No redirect has been selected for publishing', 'Skipping publishing', AbstractMessage::NOTICE);
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
}
