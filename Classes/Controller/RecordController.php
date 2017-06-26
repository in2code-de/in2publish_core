<?php
namespace In2code\In2publishCore\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Domain\Factory\FakeRecordFactory;
use In2code\In2publishCore\Domain\Service\TcaService;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Content publishing Controller. Any action is for the "Publish Records" Backend module "m1"
 */
class RecordController extends AbstractController
{
    /**
     * Create a Record instance of the current selected page
     * If none is chosen, a Record with uid = 0 is created which
     * represents the instance root
     *
     * @return void
     */
    public function indexAction()
    {
        $this->logger->debug('Called ' . __FUNCTION__);
        TcaService::getInstance();
        if (!ConfigurationUtility::getConfiguration('factory.simpleOverviewAndAjax')) {
            $record = $this->commonRepository->findByIdentifier($this->pid);
        } else {
            $record = $this->objectManager->get(
                FakeRecordFactory::class
            )->buildFromStartPage($this->pid);
        }

        $this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeIndexViewRender', [$this, $record]);

        $this->view->assign('record', $record);
        $this->assignServerAndPublishingStatus();
    }

    /**
     * Show record details (difference view) to a page
     * Normally called via AJAX
     *
     * @param int $identifier record identifier
     * @param string $tableName
     * @return void
     */
    public function detailAction($identifier, $tableName)
    {
        $this->logger->debug('Called ' . __FUNCTION__);
        $this->commonRepository->disablePageRecursion();
        $record = $this->commonRepository->findByIdentifier($identifier, $tableName);

        $this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeDetailViewRender', [$this, $record]);

        $this->view->assign('record', $record);
        $this->view->assign('configuration', ConfigurationUtility::getConfiguration());
    }

    /**
     * Check if user is allowed to publish
     *
     * @return void
     */
    public function initializePublishRecordAction()
    {
        $this->checkUserAllowedToPublish();
    }

    /**
     * Publish the selected page record with all related content records
     *
     * @param int $identifier
     * @param string $returnUrl
     * @return void
     */
    public function publishRecordAction($identifier, $returnUrl = null)
    {
        $this->logger->notice(
            'publishing page in ' . LocalizationUtility::translate($this->request->getPluginName(), 'in2publish_core'),
            ['identifier' => $identifier]
        );
        $this->publishRecord($identifier, ['pages']);
        if ($returnUrl !== null) {
            $this->redirectToUri($this->decodeReturnUrl($returnUrl));
        } else {
            $this->addSuccessFlashMessageAndRedirectToIndex();
        }
    }

    /**
     * toggle filter status and save the filter status
     * in the current backendUser's session.
     *
     * @param string $filter "changed", "added", "deleted"
     * @return void
     */
    public function toggleFilterStatusAndRedirectToIndexAction($filter)
    {
        $this->toggleFilterStatusAndRedirect('in2publish_filter_records_', $filter, 'index');
    }

    /**
     * @param int $identifier
     * @param array $exceptTableNames
     * @return void
     */
    protected function publishRecord($identifier, array $exceptTableNames = [])
    {
        $record = $this->commonRepository->findByIdentifier($identifier);

        $this->signalSlotDispatcher->dispatch(__CLASS__, 'beforePublishing', [$this, $record]);

        $this->commonRepository->publishRecordRecursive(
            $record,
            array_merge(ConfigurationUtility::getConfiguration('excludeRelatedTables'), $exceptTableNames)
        );
        $this->runTasks();
    }

    /**
     * Add success message and redirect to indexAction
     *
     * @return void
     */
    protected function addSuccessFlashMessageAndRedirectToIndex()
    {
        $this->addFlashMessage(LocalizationUtility::translate('record_published', 'in2publish_core'));
        $this->redirect('index');
    }

    /**
     * @param string $returnUrl
     * @return string
     */
    protected function decodeReturnUrl($returnUrl)
    {
        while (strpos($returnUrl, '/') === false || strpos($returnUrl, 'typo3') === false) {
            $returnUrl = rawurldecode($returnUrl);
        }
        return $returnUrl;
    }
}
