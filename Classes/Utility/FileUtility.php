<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
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

use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileUtility
 */
class FileUtility
{
    /**
     * @var Logger
     */
    protected static $logger;

    /**
     * @return void
     */
    protected static function initializeLogger()
    {
        if (static::$logger === null) {
            static::$logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        }
    }

    /**
     * Removes old Backups which are no longer needed
     */
    public static function cleanUpBackups(int $keepBackups, string $tableName, string $backupFolder)
    {
        static::initializeLogger();

        $backups = glob($backupFolder . '*_' . $tableName . '.*');

        if (\is_array($backups)
            && \is_int($keepBackups)
        ) {
            while (\count($backups) >= $keepBackups) {
                $backupFileName = array_shift($backups);
                try {
                    if (unlink($backupFileName)) {
                        static::$logger->notice('Deleted old backup "' . $backupFileName . '"');
                    } else {
                        static::$logger->error('Could not delete backup "' . $backupFileName . '"');
                    }
                } catch (\Throwable $exception) {
                    static::$logger->critical(
                        'An error occurred while deletion of "' . $backupFileName . '"',
                        [
                            'code' => $exception->getCode(),
                            'message' => $exception->getMessage(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                        ]
                    );
                }
            }
        }
    }

    /**
     * Add slashes to a given folder
     *      'folder/folder2' => '/folder/folder2',
     *      '/folder/folder2/' => '/folder/folder2'
     *      '' => '/'
     */
    public static function getCleanFolder(string $folder): string
    {
        $folder = trim($folder, '/');
        if (empty($folder)) {
            return '/';
        }
        return '/' . $folder . '/';
    }

    public static function extractFileInformation(FileInterface $file): array
    {

        $size = array_pop($file->getStorage()->getFileInfoByIdentifier($file->getIdentifier(), ['size']));

        $info = [
            'identifier' => $file->getIdentifier(),
            'storage' => $file->getStorage()->getUid(),
            'size' => $size ?? 0,
            'name' => $file->getName(),
        ];

        if ($file instanceof AbstractFile) {
            $info['uid'] = $file->getUid();
        } else {
            $info['uid'] = sprintf('%s:%s', $file->getStorage(), $file->getIdentifier());
        }

        return $info;
    }

    public static function extractFilesInformation(array $files): array
    {
        $newIndex = [];
        foreach ($files as $file) {
            $fileInfo = static::extractFileInformation($file);
            $newIndex[$fileInfo['identifier']] = $fileInfo;
        }
        return $newIndex;
    }
}
