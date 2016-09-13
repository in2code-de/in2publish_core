<?php
namespace In2code\In2publishCore\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
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

use In2code\In2publishCore\Utility\FileUtility;
use In2code\In2publishCore\Utility\FolderUtility;

/**
 * Class FilesystemCommandController (enabled on foreign)
 */
class FilesystemCommandController extends AbstractCommandController
{
    const GET_FOLDERS_IN_FOLDER_COMMAND = 'filesystem:getfoldersinfolder';
    const GET_FOLDER_INFORMATION_COMMAND = 'filesystem:getfolderinformation';
    const GET_FILES_IN_FOLDER_COMMAND = 'filesystem:getfilesinfolder';
    const REMOVE_FOLDER_RECURSIVE_COMMAND = 'filesystem:removefolderrecursive';
    const RENAME_FILE_COMMAND = 'filesystem:renamefile';
    const MD5_FILE_COMMAND = 'filesystem:filemd5';

    /**
     * @var \TYPO3\CMS\Core\Resource\ResourceFactory
     * @inject
     */
    protected $resourceFactory;

    /**
     * CommandController to show folders in a given folder
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @param string $relativePath
     * @return void
     * @internal
     */
    public function getFoldersInFolderCommand($relativePath)
    {
        $this->outputResults(FolderUtility::getFoldersInFolder($relativePath));
    }

    /**
     * CommandController to get folder information
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @param string $relativePath
     * @return void
     * @internal
     */
    public function getFolderInformationCommand($relativePath)
    {
        $this->outputResults(FolderUtility::getFolderInformation($relativePath));
    }

    /**
     * CommandController to get files in a given folder
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @param string $relativePath
     * @return void
     * @internal
     */
    public function getFilesInFolderCommand($relativePath)
    {
        $this->outputResults(FolderUtility::getFilesInFolder($relativePath));
    }

    /**
     * CommandController to remove folders
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @param string $relativePath
     * @return void
     * @internal
     */
    public function removeFolderRecursiveCommand($relativePath)
    {
        $this->resourceFactory->getStorageObject(1)->setEvaluatePermissions(false);
        $folder = $this->resourceFactory->getFolderObjectFromCombinedIdentifier('1:' . $relativePath);
        try {
            $folder->delete(false);
            $this->outputResults(true);
        } catch (\Exception $e) {
            $this->outputResults($e->getMessage());
        }
    }

    /**
     * CommandController to rename files
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @param string $oldFileIdentifier
     * @param string $newFileIdentifier
     * @return void
     * @internal
     */
    public function renameFileCommand($oldFileIdentifier, $newFileIdentifier)
    {
        $this->outputResults(array($oldFileIdentifier, $newFileIdentifier));
    }

    /**
     * Get md5 hash of relative file
     * NOTE: This command is used for internal operations in in2publish_core
     *
     * @param string $relativePathAndFile
     * @return void
     * @internal
     */
    public function fileMd5Command($relativePathAndFile)
    {
        $this->outputLine(FileUtility::hash($relativePathAndFile));
    }

    /**
     * @param array $results
     * @return void
     * @internal
     */
    public function outputResults($results = array())
    {
        $this->outputLine(base64_encode(serialize($results)));
    }
}
