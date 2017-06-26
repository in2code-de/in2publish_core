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

use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FolderUtility
 */
class FolderUtility
{
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
        $subFolder = '';
        $typo3RequestHost = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
        if ($testHost) {
            $typo3RequestHost = $testHost;
        }
        $typo3SiteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        if ($testUrl) {
            $typo3SiteUrl = $testUrl;
        }

        // if subFolder
        if ($typo3RequestHost . '/' !== $typo3SiteUrl) {
            $subFolder = substr(str_replace($typo3RequestHost . '/', '', $typo3SiteUrl), 0, -1);
        }
        if ($trailingSlash && substr($subFolder, 0, -1) !== '/') {
            $subFolder .= '/';
        }
        if ($leadingSlash && $subFolder[0] !== '/') {
            $subFolder = '/' . $subFolder;
        }
        return $subFolder;
    }

    /**
     * @param FolderInterface $folder
     * @return array
     */
    public static function extractFolderInformation(FolderInterface $folder)
    {
        return [
            'name' => $folder->getName(),
            'identifier' => $folder->getIdentifier(),
            'storage' => $folder->getStorage()->getUid(),
            'uid' => sprintf('%d:%s', $folder->getStorage()->getUid(), $folder->getIdentifier()),
        ];
    }

    /**
     * @param array $folders
     * @return array
     */
    public static function extractFoldersInformation(array $folders)
    {
        foreach ($folders as $index => $folder) {
            $folders[$index] = static::extractFolderInformation($folder);
        }
        return $folders;
    }
}
