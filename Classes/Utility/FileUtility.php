<?php
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
    protected static $logger = null;

    /**
     * @return void
     */
    protected static function initializeLogger()
    {
        if (self::$logger === null) {
            self::$logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(
                get_called_class()
            );
        }
    }

    /**
     * Removes old Backups which are no longer needed
     *
     * @param int $keepBackups
     * @param string $tableName
     * @param string $backupFolder
     * @return void
     */
    public static function cleanUpBackups($keepBackups, $tableName, $backupFolder)
    {
        self::initializeLogger();

        $backups = glob($backupFolder . '*_' . $tableName . '.*');
        while (count($backups) >= $keepBackups) {
            $backupFileName = array_shift($backups);
            try {
                if (unlink($backupFileName)) {
                    self::$logger->notice('Deleted old backup "' . $backupFileName . '"');
                } else {
                    self::$logger->error('Could not delete backup "' . $backupFileName . '"');
                }
            } catch (\Exception $exception) {
                self::$logger->critical(
                    'An error occurred while deletion of "' . $backupFileName . '"',
                    array(
                        'code' => $exception->getCode(),
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                    )
                );
            }
        }
    }

    /**
     * Add slashes to a given folder
     *      'folder/folder2' => '/folder/folder2',
     *      '/folder/folder2/' => '/folder/folder2'
     *      '' => '/'
     *
     * @param $folder
     * @return string
     */
    public static function getCleanFolder($folder)
    {
        $folder = trim($folder, '/');
        if (empty($folder)) {
            return '/';
        }
        return '/' . $folder . '/';
    }

    /**
     * @param FileInterface $file
     * @return array
     */
    public static function extractFileInformation(FileInterface $file)
    {
        return array(
            'identifier' => $file->getIdentifier(),
        );
    }

    /**
     * @param array $files
     * @return array
     */
    public static function extractFilesInformation(array $files)
    {
        $newIndex = array();
        foreach ($files as $file) {
            $fileInfo = static::extractFileInformation($file);
            $newIndex[$fileInfo['identifier']] = $fileInfo;
        }
        return $newIndex;
    }
}
