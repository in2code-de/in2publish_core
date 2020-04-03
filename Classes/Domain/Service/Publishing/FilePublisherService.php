<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Service\Publishing;

/*
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
 */

use In2code\In2publishCore\Communication\TemporaryAssetTransmission\AssetTransmitter;
use In2code\In2publishCore\Communication\TemporaryAssetTransmission\Exception\FileMissingException;
use In2code\In2publishCore\Domain\Driver\RemoteFileAbstractionLayerDriver;
use In2code\In2publishCore\Domain\Service\Publishing\Exception\UnexpectedMissingFileException;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function basename;
use function dirname;
use function file_exists;

/**
 * Class FilePublisherService
 */
class FilePublisherService
{
    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var RemoteFileAbstractionLayerDriver
     */
    protected $remoteFalDriver = null;

    /**
     * FolderPublisherService constructor.
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->remoteFalDriver = GeneralUtility::makeInstance(RemoteFileAbstractionLayerDriver::class);
    }

    /**
     * Removes a file from a foreign storage
     *
     * @param int $storage
     * @param string $fileIdentifier
     *
     * @return bool
     */
    public function removeForeignFile($storage, $fileIdentifier): bool
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
     *
     * @return bool
     */
    public function addFileToForeign($storage, $fileIdentifier): bool
    {
        $this->remoteFalDriver->setStorageUid($storage);
        $this->remoteFalDriver->initialize();

        try {
            $temporaryIdentifier = $this->transferTemporaryFile($storage, $fileIdentifier);
        } catch (FileMissingException | UnexpectedMissingFileException $e) {
            return false;
        }

        $folderIdentifier = dirname($fileIdentifier);
        if (!$this->remoteFalDriver->folderExists($folderIdentifier)) {
            $this->remoteFalDriver->createFolder(basename($folderIdentifier), dirname($folderIdentifier), true);
        }

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
     *
     * @return bool
     */
    public function updateFileOnForeign($storage, $fileIdentifier): bool
    {
        $this->remoteFalDriver->setStorageUid($storage);
        $this->remoteFalDriver->initialize();

        try {
            $temporaryIdentifier = $this->transferTemporaryFile($storage, $fileIdentifier);
        } catch (FileMissingException | UnexpectedMissingFileException $e) {
            return false;
        }

        return $this->remoteFalDriver->replaceFile($fileIdentifier, $temporaryIdentifier);
    }

    /**
     * @param int $storage
     * @param string $fileIdentifier
     * @param string $targetFolderId
     * @param string $newFileName
     *
     * @return string
     */
    public function moveForeignFile($storage, $fileIdentifier, $targetFolderId, $newFileName): string
    {
        $this->remoteFalDriver->setStorageUid($storage);
        $this->remoteFalDriver->initialize();

        return $this->remoteFalDriver->moveFileWithinStorage($fileIdentifier, $targetFolderId, $newFileName);
    }

    /**
     * @param int $storage
     * @param string $fileIdentifier
     *
     * @return string
     *
     * @throws UnexpectedMissingFileException
     * @throws FileMissingException
     */
    protected function transferTemporaryFile($storage, $fileIdentifier): string
    {
        $source = $this->getLocalReadableFilePathForIdentifier($storage, $fileIdentifier);

        if (!file_exists($source)) {
            $this->logger->error(
                'A file that should be published does not exist',
                [$storage => $storage, 'fileIdentifier' => $fileIdentifier]
            );
            throw UnexpectedMissingFileException::fromFileIdentifierAndStorage($fileIdentifier, $storage);
        }

        $assetTransmitter = GeneralUtility::makeInstance(AssetTransmitter::class);

        return $assetTransmitter->transmitTemporaryFile($source);
    }

    /**
     * @param int $storage
     * @param string $fileIdentifier
     *
     * @return string
     */
    protected function getLocalReadableFilePathForIdentifier($storage, $fileIdentifier): string
    {
        return ResourceFactory::getInstance()
                              ->getStorageObject($storage)
                              ->getFile($fileIdentifier)
                              ->getForLocalProcessing(false);
    }
}
