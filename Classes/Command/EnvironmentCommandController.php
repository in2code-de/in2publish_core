<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de
 * Alex Kellner <alexander.kellner@in2code.de>,
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

use In2code\In2publishCore\Domain\Service\Environment\FileStorageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function preg_replace;

/**
 * Class EnvironmentCommandController (always enabled)
 */
class EnvironmentCommandController extends AbstractCommandController
{
    /**
     * Rewrite not allowed characters in folders (and also in 'sys_file' database records)
     *
     *  This command rewrites not allowed characters in your file storage (e.g. fileadmin) for folders
     *  and also in their related sys_file records. German umlauts will be replaced (ä => ae).
     *  Other not allowed characters will be substituted with "_"
     *
     * @param string $fileStoragePath Folder where to start the substitution
     * @param boolean $dryRun Test how many records/files could be rewritten before doing it (pls use CLI for output)
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function rewriteNonUtf8CharactersForFoldersCommand($fileStoragePath = 'fileadmin/', $dryRun = false)
    {
        $fileStorageService = GeneralUtility::makeInstance(FileStorageService::class, $fileStoragePath);
        $this->showNonAllowedFolders($fileStorageService);
        if (!$dryRun) {
            $fileStorageService->rewriteNonAllowedFolders();
        }
    }

    /**
     * Rewrite not allowed characters in files (and also in 'sys_file' database records)
     *
     *  This command rewrites not allowed characters in your file storage (e.g. fileadmin) for files
     *  and also in their related sys_file records. German umlauts will be replaced (ä => ae).
     *  Other not allowed characters will be substituted with "_"
     *
     * @param string $fileStoragePath Folder where to start the substitution
     * @param boolean $dryRun Test how many records/files could be rewritten before doing it (pls use CLI for output)
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function rewriteNonUtf8CharactersForFilesCommand($fileStoragePath = 'fileadmin/', $dryRun = false)
    {
        $fileStorageService = GeneralUtility::makeInstance(FileStorageService::class, $fileStoragePath);
        $this->showNonAllowedFiles($fileStorageService);
        if (!$dryRun) {
            $fileStorageService->rewriteNonAllowedFiles();
        }
    }

    /**
     * @param FileStorageService $fileStorageService
     * @return void
     */
    protected function showNonAllowedFolders(FileStorageService $fileStorageService)
    {
        $folders = $fileStorageService->getNonAllowedFolders();
        if (empty($folders)) {
            $this->outputLine('No invalid folders found');
            return;
        }

        $this->outputLine('Folders with non-allowed characters:');
        $counter = 0;
        foreach ($folders as $oldFolder => $newFolder) {
            $counter++;
            $spacer = preg_replace('~\d~', ' ', $counter);
            $this->outputLine($counter . ' old: ' . $oldFolder);
            $this->outputLine($spacer . ' new: ' . $newFolder);
        }
    }

    /**
     * @param FileStorageService $fileStorageService
     * @return void
     */
    protected function showNonAllowedFiles(FileStorageService $fileStorageService)
    {
        $files = $fileStorageService->getNonAllowedFiles();
        if (empty($files)) {
            $this->outputLine('No invalid files found');
            return;
        }

        $this->outputLine('Files with non-allowed characters:');
        $counter = 0;
        foreach ($files as $oldFile => $newFile) {
            $counter++;
            $spacer = preg_replace('~\d~', ' ', $counter);
            $this->outputLine($counter . ' old: ' . $oldFile);
            $this->outputLine($spacer . ' new: ' . $newFile);
        }
    }
}
