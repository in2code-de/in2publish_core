<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Service\Publishing;

/***************************************************************
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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
 ***************************************************************/

use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

/**
 * Class FolderPublisherService
 */
class FolderPublisherService
{
    /**
     * @param string $combinedIdentifier
     * @return bool
     */
    public function publish($combinedIdentifier)
    {
        list($storage, $folderIdentifier) = GeneralUtility::trimExplode(':', $combinedIdentifier);

        $remoteFalDriver = GeneralUtility::makeInstance(RemoteFileAbstractionLayerDriver::class);
        $remoteFalDriver->setStorageUid($storage);
        $remoteFalDriver->initialize();

        // Determine if the folder should get published or deleted.
        // If it exists locally then create it on foreign else remove it.
        if (ResourceFactory::getInstance()->getStorageObject($storage)->hasFolder($folderIdentifier)) {
            $success = $remoteFalDriver->createFolder(basename($folderIdentifier), dirname($folderIdentifier), true);
        } else {
            $success = $remoteFalDriver->deleteFolder($folderIdentifier, true);
        }
        try {
            GeneralUtility::makeInstance(Dispatcher::class)->dispatch(
                FolderPublisherService::class,
                'afterPublishingFolder',
                [$storage, $folderIdentifier, ($success !== false)]
            );
        } catch (InvalidSlotException $e) {
        } catch (InvalidSlotReturnException $e) {
        }
        return $success;
    }
}
