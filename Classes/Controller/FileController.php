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

use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * The FileController is responsible for the "Publish Files" Backend module "m2"
 */
class FileController extends AbstractController
{
    /**
     *
     */
    public function indexAction()
    {
        $this->assignServerAndPublishingStatus();

        $record = $this
            ->objectManager
            ->get('In2code\\In2publishCore\\Domain\\Factory\\FolderRecordFactory')
            ->makeInstance(GeneralUtility::_GP('id'));

        $this->view->assign('record', $record);
    }

    /**
     * @param string $identifier
     */
    public function publishFolderAction($identifier)
    {
        $success = $this
            ->objectManager
            ->get('In2code\\In2publishCore\\Domain\\Service\\Publishing\\FolderPublisherService')
            ->publish($identifier);

        if ($success) {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'file_publishing.folder',
                    'in2publish_core',
                    array($identifier)
                ),
                LocalizationUtility::translate('file_publishing.success', 'in2publish_core')
            );
        } else {
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'file_publishing.failure.folder',
                    'in2publish_core',
                    array($identifier)
                ),
                LocalizationUtility::translate('file_publishing.failure', 'in2publish_core'),
                AbstractMessage::ERROR
            );
        }
        $this->redirect('index');
    }

    /**
     * @param string $identifier
     * @param int $storage
     */
    public function publishFileAction($identifier, $storage)
    {
        $record = $this
            ->objectManager
            ->get('In2code\\In2publishCore\\Domain\\Factory\\FolderRecordFactory')
            ->makeInstance($storage . ':/' . ltrim(dirname($identifier), '/'));

        $relatedRecords = $record->getRelatedRecordByTableAndProperty('sys_file', 'identifier', $identifier);

        if (count($relatedRecords) !== 1) {
            throw new \RuntimeException(
                'Did not find exactly one record that matches the publishing arguments',
                1475588793
            );
        }
        $relatedRecord = reset($relatedRecords);

        $this
            ->objectManager
            ->get(
                'In2code\\In2publishCore\\Domain\\Repository\\CommonRepository',
                DatabaseUtility::buildLocalDatabaseConnection(),
                DatabaseUtility::buildForeignDatabaseConnection(),
                'sys_file'
            )
            ->publishRecordRecursive($relatedRecord);

        $this->addFlashMessage(
            LocalizationUtility::translate(
                'file_publishing.file',
                'in2publish_core',
                array($identifier)
            ),
            LocalizationUtility::translate('file_publishing.success', 'in2publish_core')
        );

        $this->redirect('index');
    }
}
