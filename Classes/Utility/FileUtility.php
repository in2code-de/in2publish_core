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
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\File;

/**
 * Class FileUtility
 */
class FileUtility
{
    /**
     * @var string
     */
    protected static $defaultStorage = 'fileadmin/';

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
     * Get Storage from Uid
     *
     * @param $storageUid
     * @return ResourceStorage
     */
    public static function getStorage($storageUid)
    {
        /** @var \TYPO3\CMS\Core\Resource\ResourceFactory $fileFactory */
        $fileFactory = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
        return $fileFactory->getStorageObject($storageUid);
    }

    /**
     * Get storage from uid
     *
     * @param int $storageUid
     * @return string
     */
    public static function getStorageBasePath($storageUid)
    {
        $storageName = self::$defaultStorage;
        $storage = self::getStorage($storageUid);
        if ($storage !== null) {
            $properties = $storage->getConfiguration();
            $storageName = $properties['basePath'];
        }
        return $storageName;
    }

    /**
     * If an Extbase\File is given, a Core\Resource\File will be returned,
     * otherwise the return value is the argument
     *
     * @param $file
     * @return \TYPO3\CMS\Core\Resource\File
     */
    public static function unDecorateFile($file)
    {
        if ($file instanceof File) {
            $file = $file->getOriginalResource();
        }
        return $file;
    }

    /**
     * @param string $relativePath
     * @return array
     */
    public static function getFileInformation($relativePath)
    {
        $identifier = PATH_site . $relativePath;
        if (!is_file($identifier)) {
            return array();
        }
        $results = stat($identifier);
        foreach (array_keys($results) as $key) {
            if (is_int($key)) {
                unset($results[$key]);
            }
        }
        $relativeFolder = FolderUtility::getSanitizedFolderName(dirname($relativePath));

        $results['uid'] = base_convert(hexdec(md5($relativePath)), 8, 10);
        $results['name'] = basename($relativePath);
        $results['relativeParent'] = dirname($relativeFolder);
        $results['relativePath'] = $relativeFolder;
        $results['absolutePath'] = $identifier;
        $results['folderHash'] = FolderUtility::hashFolderIdentifier($relativeFolder);
        $results['identifier'] = $results['relativePath'] . $results['name'];
        $results['hash'] = self::hash($relativePath);
        return $results;
    }

    /**
     * @param string $relativePath
     * @return string
     */
    public static function hash($relativePath)
    {
        return md5_file(PATH_site . $relativePath);
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
     * @param string $relativePath beginning with fileadmin
     * @return bool
     */
    public static function fileExists($relativePath)
    {
        return is_file(PATH_site . $relativePath);
    }

    /**
     * @param string $path
     * @return void
     */
    public static function createDirectoryRecursively($path)
    {
        GeneralUtility::mkdir_deep(PATH_site . ltrim($path, '/') . '/');
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
}
