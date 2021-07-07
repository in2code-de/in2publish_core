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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

use function basename;
use function dirname;
use function file_exists;

class FilePublisherService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var RemoteFileAbstractionLayerDriver */
    protected $remoteFalDriver;

    public function __construct(RemoteFileAbstractionLayerDriver $remoteFalDriver)
    {
        $this->remoteFalDriver = $remoteFalDriver;
    }

    public function removeForeignFile(int $storage, string $fileIdentifier): bool
    {
        $this->remoteFalDriver->setStorageUid($storage);
        $this->remoteFalDriver->initialize();

        return $this->remoteFalDriver->deleteFile($fileIdentifier);
    }

    public function addFileToForeign(int $storage, string $fileIdentifier): bool
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
        // Ignore slashes because of drivers which do not return the
        // leading or trailing slashes (like fal_s3/aus_driver_amazon_s3)
        return trim($fileIdentifier, '/') === trim($newFileIdentifier, '/');
    }

    public function updateFileOnForeign(int $storage, string $fileIdentifier): bool
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

    public function moveForeignFile(int $storage, string $oldIdentifier, string $newIdentifier): string
    {
        $this->remoteFalDriver->setStorageUid($storage);
        $this->remoteFalDriver->initialize();

        $targetFolderId = PathUtility::dirname($newIdentifier);
        $newFileName = PathUtility::basename($newIdentifier);

        return $this->remoteFalDriver->moveFileWithinStorage($oldIdentifier, $targetFolderId, $newFileName);
    }

    /**
     * @throws FileMissingException
     * @throws UnexpectedMissingFileException
     */
    protected function transferTemporaryFile(int $storage, string $fileIdentifier): string
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

    protected function getLocalReadableFilePathForIdentifier(int $storage, string $fileIdentifier): string
    {
        return GeneralUtility::makeInstance(ResourceFactory::class)
                             ->getStorageObject($storage)
                             ->getFile($fileIdentifier)
                             ->getForLocalProcessing(false);
    }
}
