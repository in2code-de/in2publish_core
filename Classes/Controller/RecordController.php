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

use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Domain\Service\TcaProcessingService;
use In2code\In2publishCore\Features\SimpleOverviewAndAjax\Domain\Factory\FakeRecordFactory;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Permission\PermissionService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Content publishing Controller. Any action is for the "Publish Records" Backend module "m1"
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RecordController extends AbstractController
{
    /**
     * @var CommonRepository
     */
    protected $commonRepository = null;

    /**
     *
     */
    public function initializeAction()
    {
        parent::initializeAction();
        if (static::BLANK_ACTION !== $this->actionMethodName) {
            $this->commonRepository = CommonRepository::getDefaultInstance();
        }
    }

    /**
     * Create a Record instance of the current selected page
     * If none is chosen, a Record with uid = 0 is created which
     * represents the instance root
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function indexAction()
    {
        $this->logger->debug('Called indexAction');
        TcaProcessingService::getInstance();
        if (!$this->configContainer->get('factory.simpleOverviewAndAjax')) {
            $record = $this->commonRepository->findByIdentifier($this->pid);
        } else {
            $record = GeneralUtility::makeInstance(FakeRecordFactory::class)->buildFromStartPage($this->pid);
        }

        $this->view->assign('record', $record);
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
        $this->logger->debug('Called detailAction');
        $this->commonRepository->disablePageRecursion();
        $record = $this->commonRepository->findByIdentifier($identifier, $tableName);

        try {
            $this->signalSlotDispatcher->dispatch(__CLASS__, 'beforeDetailViewRender', [$this, $record]);
        } catch (InvalidSlotException $e) {
        } catch (InvalidSlotReturnException $e) {
        }

        $this->view->assign('record', $record);
    }

    /**
     * Check if user is allowed to publish
     *
     * @throws In2publishCoreException
     */
    public function initializePublishRecordAction()
    {
        if (!GeneralUtility::makeInstance(PermissionService::class)->isUserAllowedToPublish()) {
            throw new In2publishCoreException('You are not allowed to publish', 1435306780);
        }
    }

    /**
     * Publish the selected page record with all related content records
     *
     * @param int $identifier
     * @param string $returnUrl
     *
     * @throws StopActionException
     */
    public function publishRecordAction($identifier, $returnUrl = null)
    {
        $this->logger->notice('publishing record in ' . $this->request->getPluginName(), ['identifier' => $identifier]);
        $this->publishRecord($identifier, ['pages']);
        if ($returnUrl !== null) {
            while (strpos($returnUrl, '/') === false || strpos($returnUrl, 'typo3') === false) {
                $returnUrl = rawurldecode($returnUrl);
            }
            try {
                $this->redirectToUri($returnUrl);
            } catch (UnsupportedRequestTypeException $e) {
            }
        }
        $this->addSuccessFlashMessageAndRedirectToIndex();
    }

    /**
     * toggle filter status and save the filter status
     * in the current backendUser's session.
     *
     * @param string $filter "changed", "added", "deleted"
     *
     * @throws StopActionException
     */
    public function toggleFilterStatusAndRedirectToIndexAction($filter)
    {
        $this->toggleFilterStatusAndRedirect('in2publish_filter_records_', $filter, 'index');
    }

    /**
     * @param int $identifier
     * @param array $exceptTableNames
     */
    protected function publishRecord($identifier, array $exceptTableNames = [])
    {
        $record = $this->commonRepository->findByIdentifier($identifier);

        try {
            $this->signalSlotDispatcher->dispatch(__CLASS__, 'beforePublishing', [$this, $record]);
        } catch (InvalidSlotException $exception) {
        } catch (InvalidSlotReturnException $exception) {
        }

        try {
            $this->commonRepository->publishRecordRecursive(
                $record,
                array_merge($this->configContainer->get('excludeRelatedTables'), $exceptTableNames)
            );
        } catch (\Exception $exception) {
            $this->logger->error('Error while publishing', ['exception' => $exception]);
            $this->addFlashMessage($exception->getMessage(), AbstractMessage::ERROR);
        }
        $this->runTasks();
    }

    /**
     * Add success message and redirect to indexAction
     *
     * @throws StopActionException
     */
    protected function addSuccessFlashMessageAndRedirectToIndex()
    {
        $this->addFlashMessage(LocalizationUtility::translate('record_published', 'in2publish_core'));
        try {
            $this->redirect('index');
        } catch (UnsupportedRequestTypeException $e) {
        }
    }
}
