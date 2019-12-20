<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Service\Environment;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de
 * Alex Kellner <alexander.kellner@in2code.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use function array_key_exists;
use function array_keys;
use function array_pop;
use function array_values;
use function explode;
use function is_dir;
use function is_file;
use function pathinfo;
use function preg_replace;
use function rename;
use function rtrim;
use function str_replace;
use function uniqid;

/**
 * Class FileStorageEnvironmentService finds not allowed entries in files and folders
 */
class FileStorageService extends AbstractService
{
    /**
     * [
     *  $originalPath1 => $newPath1,
     *  $originalPath2 => $newPath2
     * ]
     *
     * @var array
     */
    protected $nonAllowedFolders = [];

    /**
     * [
     *  $originalFileAndFolder1 => $newFileAndFolder1,
     *  $originalFileAndFolder2 => $newFileAndFolder2
     * ]
     *
     * @var array
     */
    protected $nonAllowedFiles = [];

    /**
     * FileStorageEnvironmentService constructor.
     *
     * @param $fileStoragePath
     */
    public function __construct($fileStoragePath)
    {
        $this->fileStoragePath = $fileStoragePath;
        $this->setNonAllowedFilesAndFolders();
    }

    /**
     * Rewrite non allowed folders and there sys_file entries
     *
     * @return void
     */
    public function rewriteNonAllowedFolders()
    {
        foreach ($this->getNonAllowedFolders() as $oldFolder => $newFolder) {
            $this->renameFolder($oldFolder, $newFolder);
        }
    }

    /**
     * Rewrite non allowed files and there sys_file entries
     *
     * @return void
     */
    public function rewriteNonAllowedFiles()
    {
        foreach ($this->getNonAllowedFiles() as $oldFile => $newFile) {
            $this->renameFile($oldFile, $newFile);
        }
    }

    /**
     * @return array
     */
    public function getNonAllowedFolders(): array
    {
        return $this->nonAllowedFolders;
    }

    /**
     * @return array
     */
    public function getNonAllowedFiles(): array
    {
        return $this->nonAllowedFiles;
    }

    /**
     * Get a list of non allowed files and folders
     */
    protected function setNonAllowedFilesAndFolders()
    {
        foreach ($this->getFilesAndFoldersRecursive($this->fileStoragePath) as $fileOrFolder) {
            if ($this->isFolder($fileOrFolder)) {
                if ($this->isNonAllowedFolder($fileOrFolder)) {
                    $fileOrFolder = $this->renameParentPathInFileOrFolder($fileOrFolder);
                    $this->nonAllowedFolders[$fileOrFolder] = $this->getNewFolderName($fileOrFolder);
                }
            } elseif ($this->isNonAllowedFile($fileOrFolder)) {
                $this->nonAllowedFiles[$fileOrFolder] = $this->getNewFileName($fileOrFolder);
            }
        }
    }

    /**
     * @param string $pathAndFilename
     *
     * @return string
     */
    protected function getNewFileName($pathAndFilename): string
    {
        $newPathAndFilename = $this->recommendedFileName($pathAndFilename);
        if (is_file($newPathAndFilename)) {
            $pathInfo = pathinfo($pathAndFilename);
            $newPathAndFilename = $pathInfo['dirname'] . '/'
                                  . uniqid() . '.' . $pathInfo['extension'];
        }
        return $newPathAndFilename;
    }

    /**
     * @param string $folderName
     *
     * @return string
     */
    protected function getNewFolderName($folderName): string
    {
        $newFolderName = $this->recommendedFolderName($folderName);
        if (is_dir($newFolderName)) {
            $newFolderName = rtrim($newFolderName, '/');
            $newFolderName .= uniqid() . '/';
        }
        return $newFolderName;
    }

    /**
     * Get all files and folder with recursion level 99
     *
     * @param string $fileStorage
     *
     * @return array
     */
    protected function getFilesAndFoldersRecursive($fileStorage): array
    {
        return GeneralUtility::getAllFilesAndFoldersInPath(
            [],
            GeneralUtility::getFileAbsFileName($fileStorage),
            '',
            true,
            99
        );
    }

    /**
     * @param string $fileAndFolder
     *
     * @return bool
     */
    protected function isNonAllowedFile($fileAndFolder): bool
    {
        $parts = explode('/', $fileAndFolder);
        $file = array_pop($parts);
        return $file !== preg_replace($this->pattern, $this->substituteCharacter, $file);
    }

    /**
     * @param string $folder
     *
     * @return bool
     */
    protected function isNonAllowedFolder($folder): bool
    {
        return $folder !== $this->recommendedFolderName($folder);
    }

    /**
     * @param string $folder
     *
     * @return string
     */
    protected function recommendedFolderName($folder): string
    {
        $folder = str_replace(array_keys($this->rewriteCharacters), array_values($this->rewriteCharacters), $folder);
        return preg_replace($this->pattern, $this->substituteCharacter, $folder);
    }

    /**
     * @param string $filename
     *
     * @return string
     */
    protected function recommendedFileName($filename): string
    {
        $filename = str_replace(
            array_keys($this->rewriteCharacters),
            array_values($this->rewriteCharacters),
            $filename
        );
        return preg_replace($this->pattern, $this->substituteCharacter, $filename);
    }

    /**
     * @param string $oldFile
     * @param string $newFile
     *
     * @return void
     */
    protected function renameFile($oldFile, $newFile)
    {
        $this->renamePhysicalFolderOrFile($oldFile, $newFile);
        $sysFileService = GeneralUtility::makeInstance(SysFileService::class);
        $sysFileService->renameSysFile($oldFile, $newFile);
    }

    /**
     * @param string $oldFolder
     * @param string $newFolder
     *
     * @return void
     */
    protected function renameFolder($oldFolder, $newFolder)
    {
        $this->renamePhysicalFolderOrFile($oldFolder, $newFolder);
        $sysFileService = GeneralUtility::makeInstance(SysFileService::class);
        $sysFileService->renameFolderInSysFile($oldFolder, $newFolder);
    }

    /**
     * Rename fileOrFolder if parent path was already renamed
     *
     * @param $fileOrFolder
     *
     * @return mixed
     */
    protected function renameParentPathInFileOrFolder($fileOrFolder)
    {
        $parentPath = $this->removeLastFolderFromPath($fileOrFolder);
        if ($this->isFolder($fileOrFolder)) {
            if (array_key_exists($parentPath, $this->nonAllowedFolders)) {
                $newPath = $this->nonAllowedFolders[$parentPath];
                $fileOrFolder = str_replace($parentPath, $newPath, $fileOrFolder);
                return $fileOrFolder;
            }
        }
        return $fileOrFolder;
    }

    /**
     * @param string $oldFolder
     * @param string $newFolder
     */
    protected function renamePhysicalFolderOrFile($oldFolder, $newFolder)
    {
        rename($oldFolder, $newFolder);
        GeneralUtility::fixPermissions($newFolder);
    }
}
