<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Driver;

/***************************************************************
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
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

use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
abstract class AbstractLimitedFilesystemDriver extends AbstractHierarchicalFilesystemDriver implements DriverInterface
{
    /**
     * Not required
     *
     * @return string
     */
    public function getDefaultFolder(): string
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201277);
    }

    /**
     * Renames a folder in this storage.
     *
     * @param string $folderIdentifier
     * @param string $newName
     * @return array A map of old to new file identifiers of all affected resources
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function renameFolder($folderIdentifier, $newName): array
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201295);
    }

    /**
     * Returns the number of files inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $filterCallbacks callbacks for filtering the items
     * @return int Number of files in folder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filterCallbacks = []): int
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201312);
    }

    /**
     * Returns the number of folders inside the specified path
     *
     * @param string $folderIdentifier
     * @param bool $recursive
     * @param array $fnFc callbacks for filtering the items
     * @return int Number of folders in folder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $fnFc = []): int
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201325);
    }

    /**
     * Returns the identifier of a folder inside the folder
     *
     * @param string $folderName The name of the target folder
     * @param string $folderIdentifier
     * @return string folder identifier
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFolderInFolder($folderName, $folderIdentifier): string
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201370);
    }

    /**
     * Returns the identifier of a file inside the folder
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return string file identifier
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFileInFolder($fileName, $folderIdentifier): string
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201421);
    }

    /**
     * Directly output the contents of the file to the output
     * buffer. Should not take care of header files or flushing
     * buffer before. Will be taken care of by the Storage.
     *
     * @param string $identifier
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function dumpFileContents($identifier)
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201434);
    }

    /**
     * Checks if a given identifier is within a container, e.g. if
     * a file or folder is within another folder.
     * This can e.g. be used to check for web-mounts.
     *
     * Hint: this also needs to return TRUE if the given identifier
     * matches the container identifier to allow access to the root
     * folder of a filemount.
     *
     * @param string $folderIdentifier
     * @param string $identifier identifier to be checked against $folderIdentifier
     * @return bool TRUE if $content is within or matches $folderIdentifier
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isWithin($folderIdentifier, $identifier): bool
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201437);
    }

    /**
     * Returns the contents of a file. Beware that this requires to load the
     * complete file into memory and also may require fetching the file from an
     * external location. So this might be an expensive operation (both in terms
     * of processing resources and money) for large files.
     *
     * @param string $fileIdentifier
     * @return string The file contents
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFileContents($fileIdentifier): string
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201459);
    }

    /**
     * Sets the contents of a file to the specified value.
     *
     * @param string $fileIdentifier
     * @param string $contents
     * @return int The number of bytes written to the file
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setFileContents($fileIdentifier, $contents): int
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201462);
    }

    /**
     * Checks if a file inside a folder exists
     *
     * @param string $fileName
     * @param string $folderIdentifier
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fileExistsInFolder($fileName, $folderIdentifier): bool
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201464);
    }

    /**
     * Checks if a folder inside a folder exists.
     *
     * @param string $folderName
     * @param string $folderIdentifier
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function folderExistsInFolder($folderName, $folderIdentifier): bool
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201467);
    }

    /**
     * Returns a path to a local copy of a file for processing it. When changing the
     * file, you have to take care of replacing the current version yourself!
     *
     * @param string $fileIdentifier
     * @param bool $writable Set this to FALSE if you only need the file for read
     *                       operations. This might speed up things, e.g. by using
     *                       a cached local version. Never modify the file if you
     *                       have set this flag!
     * @return string The path to the file on the local disk
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getFileForLocalProcessing($fileIdentifier, $writable = true): string
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201472);
    }

    /**
     * Folder equivalent to moveFileWithinStorage().
     *
     * @param string $sourceIdentifier
     * @param string $targetIdentifier
     * @param string $newFolderName
     * @return array All files which are affected, map of old => new file identifiers
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function moveFolderWithinStorage($sourceIdentifier, $targetIdentifier, $newFolderName): array
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201494);
    }

    /**
     * Folder equivalent to copyFileWithinStorage().
     *
     * @param string $sourceIdentifier
     * @param string $targetIdentifier
     * @param string $newFolderName
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function copyFolderWithinStorage($sourceIdentifier, $targetIdentifier, $newFolderName): bool
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201496);
    }

    /**
     * Creates a new (empty) file and returns the identifier.
     *
     * @param string $fileName
     * @param string $parentIdentifier
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createFile($fileName, $parentIdentifier): string
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201512);
    }

    /**
     * Copies a file *within* the current storage.
     * Note that this is only about an inner storage copy action,
     * where a file is just copied to another folder in the same storage.
     *
     * @param string $fileIdentifier
     * @param string $targetIdentifier
     * @param string $fileName
     * @return string the Identifier of the new file
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function copyFileWithinStorage($fileIdentifier, $targetIdentifier, $fileName): string
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201516);
    }

    /**
     * Checks if a folder contains files and (if supported) other folders.
     *
     * @param string $folderIdentifier
     * @return bool TRUE if there are no files and folders within $folder
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isFolderEmpty($folderIdentifier): bool
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201527);
    }

    /**
     * Not required
     *
     * @param int $capabilities
     * @return int
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function mergeConfigurationCapabilities($capabilities): int
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201544);
    }

    /**
     * Never called
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function processConfiguration()
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201555);
    }

    /**
     * Not required
     *
     * @param int $capability
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function hasCapability($capability): bool
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201628);
    }

    /**
     * Not required
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getRootLevelFolder(): string
    {
        throw new \BadMethodCallException('The method ' . __METHOD__ . ' is not supported by this driver', 1476201635);
    }
}
