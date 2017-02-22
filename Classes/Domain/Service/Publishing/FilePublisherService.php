<?php
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
use In2code\In2publishCore\Security\SshConnection;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FilePublisherService
 */
class FilePublisherService
{
    /**
     * @var RemoteFileAbstractionLayerDriver
     */
    protected $remoteFalDriver = null;

    /**
     * FolderPublisherService constructor.
     */
    public function __construct()
    {
        $this->remoteFalDriver = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Driver\\RemoteFileAbstractionLayerDriver'
        );
    }

    /**
     * Removes a file from a foreign storage
     *
     * @param int $storage
     * @param string $fileIdentifier
     * @return bool
     */
    public function removeForeignFile($storage, $fileIdentifier)
    {
        $this->remoteFalDriver->setStorageUid($storage);
        $this->remoteFalDriver->initialize();

        return $this->remoteFalDriver->deleteFile($fileIdentifier);
    }

    /**
     * Adds a file to a foreign storage
     *
     * @param int $storage
     * @param string $fileIdentifier
     * @return bool
     */
    public function addFileToForeign($storage, $fileIdentifier)
    {
        $this->remoteFalDriver->setStorageUid($storage);
        $this->remoteFalDriver->initialize();

        $temporaryIdentifier = $this->transferTemporaryFile($storage, $fileIdentifier);

        $folderIdentifier = dirname($fileIdentifier);
        if (!$this->remoteFalDriver->folderExists($folderIdentifier)) {
            $this->remoteFalDriver->createFolder(basename($folderIdentifier), dirname($folderIdentifier), true);
        }

        $this->remoteFalDriver->setStorageUid($storage);
        $newFileIdentifier = $this->remoteFalDriver->addFile(
            $temporaryIdentifier,
            $folderIdentifier,
            basename($fileIdentifier),
            true
        );
        return $fileIdentifier === $newFileIdentifier;
    }

    /**
     * @param string $storage
     * @param string $fileIdentifier
     * @return bool
     */
    public function updateFileOnForeign($storage, $fileIdentifier)
    {
        $this->remoteFalDriver->setStorageUid($storage);
        $this->remoteFalDriver->initialize();

        $temporaryIdentifier = $this->transferTemporaryFile($storage, $fileIdentifier);

        return $this->remoteFalDriver->replaceFile($fileIdentifier, $temporaryIdentifier);
    }

    /**
     * @param int $storage
     * @param string $fileIdentifier
     * @param string $targetFolderIdentifier
     * @param string $newFileName
     * @return bool
     */
    public function moveForeignFile($storage, $fileIdentifier, $targetFolderIdentifier, $newFileName)
    {
        $this->remoteFalDriver->setStorageUid($storage);
        $this->remoteFalDriver->initialize();

        return $this->remoteFalDriver->moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName);
    }

    /**
     * @param int $storage
     * @param string $fileIdentifier
     * @return string
     */
    protected function transferTemporaryFile($storage, $fileIdentifier)
    {
        return SshConnection::makeInstance()->transferTemporaryFile(
            $this->getLocalReadableFilePathForIdentifier($storage, $fileIdentifier)
        );
    }

    /**
     * @param int $storage
     * @param string $fileIdentifier
     * @return string
     */
    protected function getLocalReadableFilePathForIdentifier($storage, $fileIdentifier)
    {
        return ResourceFactory::getInstance()
                              ->getStorageObject($storage)
                              ->getFile($fileIdentifier)
                              ->getForLocalProcessing(false);
    }
}
