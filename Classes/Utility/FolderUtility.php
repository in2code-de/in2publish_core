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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FolderUtility
 */
class FolderUtility
{
    /**
     * @param string $relativePath
     * @return array
     */
    public static function getFoldersInFolder($relativePath)
    {
        $identifier = PATH_site . $relativePath;
        if (!is_dir($identifier)) {
            return array();
        }
        $results = scandir($identifier);
        $folders = array();
        foreach ($results as $result) {
            if ($result !== '.' && $result !== '..') {
                $relativeIdentifier = $relativePath . $result;
                $absoluteIdentifier = PATH_site . $relativeIdentifier;
                if (is_dir($absoluteIdentifier) && !is_file($absoluteIdentifier)) {
                    $folders[] = self::getFolderInformation($relativeIdentifier);
                }
            }
        }
        return $folders;
    }

    /**
     * Returns all information about a folder relative to the
     * document root where only associative entries are returned
     * but additional information is added.
     * The UID value of stat will be overwritten
     *
     * @param string $relativePath relative path part
     * @return array
     */
    public static function getFolderInformation($relativePath)
    {
        $identifier = PATH_site . $relativePath;
        if (!is_dir($identifier)) {
            return array();
        }
        $results = stat($identifier);
        foreach (array_keys($results) as $key) {
            if (is_int($key)) {
                unset($results[$key]);
            }
        }
        $results['uid'] = base_convert(hexdec(md5($relativePath)), 8, 10);
        $results['name'] = basename($relativePath);
        $results['relativeParent'] = dirname($relativePath) . '/';
        $results['relativePath'] = $relativePath;
        $results['absolutePath'] = $identifier;
        return $results;
    }

    /**
     * @param string $relativePath
     * @return array
     */
    public static function getFilesInFolder($relativePath)
    {
        $identifier = PATH_site . $relativePath;
        if (!is_dir($identifier)) {
            return array();
        }
        $results = scandir($identifier);
        $files = array();
        foreach ($results as $result) {
            $relativeIdentifier = $relativePath . $result;
            $absoluteIdentifier = PATH_site . $relativeIdentifier;
            if (!is_dir($absoluteIdentifier) && is_file($absoluteIdentifier)) {
                $files[] = FileUtility::getFileInformation($relativeIdentifier);
            }
        }
        return $files;
    }

    /**
     * @param string $folderName
     * @return string
     */
    public static function getSanitizedFolderName($folderName)
    {
        if (strpos($folderName, 'fileadmin') === 0) {
            $folderName = substr($folderName, 9);
        }
        return rtrim(str_replace('//', '/', $folderName), '/') . '/';
    }

    /**
     * @param string $folderIdentifier
     * @return string
     */
    public static function hashFolderIdentifier($folderIdentifier)
    {
        $folderName = rtrim($folderIdentifier, '/');
        if (strlen($folderName) === 0) {
            $folderName = '/';
        }
        return sha1($folderName);
    }

    /**
     * Get Subfolder of current TYPO3 Installation
     *        and never return "//"
     *
     * @param bool $leadingSlash will be prepended
     * @param bool $trailingSlash will be appended
     * @param string $testHost can be used for a test
     * @param string $testUrl can be used for a test
     * @return string
     */
    public static function getSubFolderOfCurrentUrl(
        $leadingSlash = true,
        $trailingSlash = true,
        $testHost = null,
        $testUrl = null
    ) {
        $subfolder = '';
        $typo3RequestHost = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
        if ($testHost) {
            $typo3RequestHost = $testHost;
        }
        $typo3SiteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        if ($testUrl) {
            $typo3SiteUrl = $testUrl;
        }

        // if subfolder
        if ($typo3RequestHost . '/' !== $typo3SiteUrl) {
            $subfolder = substr(str_replace($typo3RequestHost . '/', '', $typo3SiteUrl), 0, -1);
        }
        if ($trailingSlash && substr($subfolder, 0, -1) !== '/') {
            $subfolder .= '/';
        }
        if ($leadingSlash && $subfolder[0] !== '/') {
            $subfolder = '/' . $subfolder;
        }
        return $subfolder;
    }
}
