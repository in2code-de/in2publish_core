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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Security\Exceptions\CommandFailedException;
use In2code\In2publishCore\Security\SshConnection;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * The FileController is responsible for the "Publish Files" Backend module "m2"
 */
class FileController extends AbstractController
{
    /**
     * @var string
     */
    protected $folderIdentifier = 'fileadmin/';

    /**
     * @var \In2code\In2publishCore\Domain\Factory\FolderRecordFactory
     * @inject
     */
    protected $folderRecordFactory = null;

    /**
     * Assigns all Files of the current folder to the view.
     * any other folder, sibling - parent - child, is ignored
     *
     * !!! Do NOT use $this->tryToGetFolderInstance() in this action, it will cause a loop !!!
     *
     * @return void
     */
    public function indexAction()
    {
        $this->assignServerAndPublishingStatus();
        try {
            $this->view->assign('record', $this->folderRecordFactory->makeInstance($this->folderIdentifier));
        } catch (CommandFailedException $exception) {
            $this->addFailureFlashMessage(
                LocalizationUtility::translate(
                    'folder_retrieving_failed',
                    'in2publish_core',
                    array($this->folderIdentifier)
                )
            );
        }
    }

    /**
     * @param string $identifier
     * @return void
     */
    public function publishFolderAction($identifier)
    {
        $folder = $this->tryToGetFolderInstance($identifier);
        $this->logger->notice('publishing folder', array('identifier' => $identifier));
        switch ($folder->getState()) {
            case RecordInterface::RECORD_STATE_ADDED:
                if (SshConnection::makeInstance()->createFolderOnRemote($folder)) {
                    $this->addFlashMessage(LocalizationUtility::translate('folder_created', 'in2publish_core'));
                } else {
                    $this->addFailureFlashMessage(
                        LocalizationUtility::translate('folder_creation_failed', 'in2publish_core')
                    );
                }
                break;
            case RecordInterface::RECORD_STATE_DELETED:
                $message = SshConnection::makeInstance()->removeFolderFromRemote($folder);
                if ($message === true) {
                    $this->addFlashMessage(LocalizationUtility::translate('folder_removed', 'in2publish_core'));
                } else {
                    $this->addFailureFlashMessage(
                        $message,
                        LocalizationUtility::translate('folder_removal_failed', 'in2publish_core')
                    );
                }
                break;
            default:
        }
        $this->redirect('index');
    }

    /**
     * @param int $identifier
     * @return void
     */
    public function publishRecordAction($identifier)
    {
        $this->logger->notice('publishing file', array('identifier' => $identifier));
        $sysFileInstance = $this->commonRepository->findByIdentifier($identifier);
        if ($sysFileInstance->getState() === RecordInterface::RECORD_STATE_UNCHANGED) {
            $sysFileInstance = $this->folderRecordFactory->addInformationAboutPhysicalFile($sysFileInstance);
        }
        $this->commonRepository->publishRecordRecursive($sysFileInstance);
        $this->addFlashMessage(
            sprintf(
                LocalizationUtility::translate('publishing.publish_file_success', 'in2publish_core'),
                $sysFileInstance->getMergedProperty('name')
            )
        );
        $this->redirect('index');
    }

    /**
     * @param string $identifier
     * @param int $uid
     */
    public function publishPhysicalRecordAction($identifier, $uid)
    {
        $folderInstance = $this->tryToGetFolderInstance(
            str_replace('//', '/', 'fileadmin' . dirname($identifier) . '/')
        );
        $relatedRecords = $folderInstance->getRelatedRecords();
        /** @var Record $record */
        $record = $relatedRecords['physical_file'][$uid];
        switch ($record->getState()) {
            case RecordInterface::RECORD_STATE_DELETED:
                $sshConnection = SshConnection::makeInstance();
                if ($sshConnection->removeRemoteFile('fileadmin' . $identifier)) {
                    $this->addFlashMessage(
                        LocalizationUtility::translate(
                            'publishing.physical_file_deleted_success',
                            'in2publish_core',
                            array(basename($identifier))
                        )
                    );
                } else {
                    $this->addFailureFlashMessage(
                        LocalizationUtility::translate(
                            'publishing.physical_file_deleted_failure',
                            'in2publish_core',
                            array(basename($identifier))
                        )
                    );
                }
                break;
            case RecordInterface::RECORD_STATE_ADDED:
                $this->logger->error(
                    'Can not publish a file to foreign without sys_file record',
                    array('identifier' => $identifier)
                );
                $this->addFailureFlashMessage(
                    LocalizationUtility::translate('publishing.physical_file_add_failure', 'in2publish_core')
                );
                break;
            case RecordInterface::RECORD_STATE_MOVED:
                $this->logger->error(
                    'Can not move a file on foreign without sys_file record',
                    array('identifier' => $identifier)
                );
                $this->addFailureFlashMessage('Something went wrong. Please contact your admin.');
                break;
            case RecordInterface::RECORD_STATE_CHANGED:
                $this->logger->error(
                    'Can not change a file on foreign without sys_file record',
                    array('identifier' => $identifier)
                );
                $this->addFailureFlashMessage('Something went wrong. Please contact your admin.');
                break;
            case RecordInterface::RECORD_STATE_UNCHANGED:
                $this->logger->emergency(
                    'Detected physical file without sys_file record which is unchanged',
                    array('identifier' => $identifier)
                );
                $this->addFailureFlashMessage('Something went wrong. Please contact your admin.');
                break;
        }
        $this->redirect('index');
    }

    /**
     * FileController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $combinedIdentifier = GeneralUtility::_GP('id');
        if ($combinedIdentifier !== null) {
            $this->folderIdentifier = str_replace('1:/', 'fileadmin/', $combinedIdentifier);
        } else {
            $fileStorages = $this->getBackendUser()->getFileStorages();
            $fileStorage = reset($fileStorages);
            // Assume that the storage with uid 1 is always fileadmin.
            // This is really bad, but we don't want to break this assumption at this point.
            // Will be changed when this module is refactored to be based completely on FAL
            if (false !== $fileStorage && 1 === $fileStorage->getUid()) {
                $folderObject = $fileStorage->getRootLevelFolder();
                if ($folderObject instanceof Folder) {
                    $this->folderIdentifier = 'fileadmin/' . ltrim($folderObject->getIdentifier(), '/');
                }
            }
        }
    }

    /**
     * @param string $message
     * @param string $title
     * @return void
     */
    protected function addFailureFlashMessage($message, $title = '')
    {
        $this->addFlashMessage(
            $message,
            $title ? $title : LocalizationUtility::translate('error', 'in2publish_core'),
            AbstractMessage::ERROR
        );
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
        $this->logger->debug('Called ' . __FUNCTION__, array('filter', $filter));
        $currentStatus = $this->backendUser->getSessionData('in2publish_filter_files_' . $filter);
        $this->logger->debug(
            'Retrieved currentStatus from filter files session',
            array('currentStatus' => $currentStatus)
        );
        if (!is_bool($currentStatus)) {
            $currentStatus = false;
        }
        $this->backendUser->setAndSaveSessionData('in2publish_filter_files_' . $filter, !$currentStatus);
        $this->redirect('index');
    }

    /**
     * @param string $identifier
     * @return Record
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    protected function tryToGetFolderInstance($identifier)
    {
        try {
            return $this->folderRecordFactory->makeInstance($identifier);
        } catch (CommandFailedException $exception) {
            $this->redirect('index');
        }
    }

    /**
     * @return void
     */
    public function initializeAction()
    {
        parent::initializeAction();
        $this->commonRepository->setTableName('sys_file');
    }
}
