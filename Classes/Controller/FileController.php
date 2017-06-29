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

use In2code\In2publishCore\Domain\Factory\Exception\TooManyForeignFilesException;
use In2code\In2publishCore\Domain\Factory\Exception\TooManyLocalFilesException;
use In2code\In2publishCore\Domain\Factory\FolderRecordFactory;
use In2code\In2publishCore\Domain\Factory\IndexingFolderRecordFactory;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Domain\Service\Publishing\FolderPublisherService;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * The FileController is responsible for the "Publish Files" Backend module "m2"
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FileController extends AbstractController
{
    const EXCEPTION_MESSAGE_PATTERN = '~The folder "(?P<folder>[^"]+)" has too many files(?: \((?P<number>\d+)\))?~';

    /**
     *
     */
    public function indexAction()
    {
        $this->assignServerAndPublishingStatus();

        $record = $this->tryToGetFolderInstance(GeneralUtility::_GP('id'));

        if (null !== $record) {
            $this->view->assign('record', $record);
        }
    }

    /**
     * @param \Exception $exception
     */
    protected function displayTooManyFilesError(\Exception $exception)
    {
        if (1 === preg_match(static::EXCEPTION_MESSAGE_PATTERN, $exception->getMessage(), $matches)) {
            // Do not remove the space at the end of ') ', because it can't
            // be included dynamically in the label where this value is used!
            $arguments = [$matches['folder'], '(' . $matches['number'] . ') '];
        } else {
            $arguments = [GeneralUtility::_GP('id'), ''];
        }

        $this->addFlashMessage(
            LocalizationUtility::translate('file_publishing.too_many_files', 'in2publish_core', $arguments),
            LocalizationUtility::translate('file_publishing.failure', 'in2publish_core'),
            AbstractMessage::WARNING
        );
    }

    /**
     * @param string $identifier
     */
    public function publishFolderAction($identifier)
    {
        $success = GeneralUtility::makeInstance(FolderPublisherService::class)->publish($identifier);

        if ($success) {
            $this->addFlashMessage(
                LocalizationUtility::translate('file_publishing.folder', 'in2publish_core', [$identifier]),
                LocalizationUtility::translate('file_publishing.success', 'in2publish_core')
            );
        } else {
            $this->addFlashMessage(
                LocalizationUtility::translate('file_publishing.failure.folder', 'in2publish_core', [$identifier]),
                LocalizationUtility::translate('file_publishing.failure', 'in2publish_core'),
                AbstractMessage::ERROR
            );
        }

        $this->redirect('index');
    }

    /**
     * @param int $uid
     * @param string $identifier
     * @param int $storage
     */
    public function publishFileAction($uid, $identifier, $storage)
    {
        // Special case: The file was moved hence the identifier is a merged one
        if (strpos($identifier, ',')) {
            // Just take the local part of the file identifier.
            // We need the local folder identifier to instantiate the folder record.
            list($identifier) = GeneralUtility::trimExplode(',', $identifier);
        }

        $record = $this->tryToGetFolderInstance($storage . ':/' . ltrim(dirname($identifier), '/'));

        if (null !== $record) {
            $relatedRecords = $record->getRelatedRecordByTableAndProperty('sys_file', 'identifier', $identifier);

            if (0 === ($recordsCount = count($relatedRecords))) {
                throw new \RuntimeException('Did not find any record matching the publishing arguments', 1475656572);
            } elseif (1 === $recordsCount) {
                $relatedRecord = reset($relatedRecords);
            } elseif (isset($relatedRecords[$uid])) {
                $relatedRecord = $relatedRecords[$uid];
            } else {
                throw new \RuntimeException('Did not find an exact record match for the given arguments', 1475588793);
            }

            CommonRepository::getDefaultInstance('sys_file')->publishRecordRecursive($relatedRecord);

            $this->addFlashMessage(
                LocalizationUtility::translate('file_publishing.file', 'in2publish_core', [$identifier]),
                LocalizationUtility::translate('file_publishing.success', 'in2publish_core')
            );
        }

        $this->redirect('index');
    }

    /**
     * toggle filter status and save the filter status in the current backendUser's session.
     *
     * @param string $filter "changed", "added", "deleted"
     * @return void
     */
    public function toggleFilterStatusAndRedirectToIndexAction($filter)
    {
        $this->toggleFilterStatusAndRedirect('in2publish_filter_files_', $filter, 'index');
    }

    /**
     * @param string $identifier CombinedIdentifier as FAL would use it
     * @return RecordInterface|null The record or null if it can not be handled
     */
    protected function tryToGetFolderInstance($identifier)
    {
        try {
            if (false === ConfigurationUtility::getConfiguration('factory.fal.reserveSysFileUids')) {
                $record = GeneralUtility::makeInstance(IndexingFolderRecordFactory::class)->makeInstance($identifier);
            } else {
                $record = GeneralUtility::makeInstance(FolderRecordFactory::class)->makeInstance($identifier);
            }
            $this->signalSlotDispatcher->dispatch(
                FileController::class,
                'folderInstanceCreated',
                [$record]
            );
            return $record;
        } catch (TooManyLocalFilesException $exception) {
            $this->displayTooManyFilesError($exception);
        } catch (TooManyForeignFilesException $exception) {
            $this->displayTooManyFilesError($exception);
        }
        return null;
    }
}
