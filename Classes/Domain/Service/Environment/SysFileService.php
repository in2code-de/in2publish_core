<?php
namespace In2code\In2publishCore\Domain\Service\Environment;

use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class SysFileService finds not allowed file entries in sys_file
 *
 * @package In2code\In2publish\Domain\Service\Environment
 */
class SysFileService extends AbstractService
{
    const TABLE_NAME = 'sys_file';

    /**
     * @var DatabaseConnection
     */
    protected $databaseConnection = null;

    /**
     * SysFileService constructor.
     */
    public function __construct()
    {
        $this->databaseConnection = DatabaseUtility::buildLocalDatabaseConnection();
    }

    /**
     * @param string $oldFolder
     * @param string $newFolder
     */
    public function renameFolderInSysFile($oldFolder, $newFolder)
    {
        $oldIdentifierPath = $this->getIdentifierFromAbsolutePath($oldFolder);
        $newIdentifierPath = $this->getIdentifierFromAbsolutePath($newFolder);
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid,identifier',
            self::TABLE_NAME,
            'identifier like "%' . $oldIdentifierPath . '%"'
        );
        foreach ($rows as $row) {
            $this->databaseConnection->exec_UPDATEquery(
                self::TABLE_NAME,
                'uid=' . (int)$row['uid'],
                $this->getArgumentsForSysFileFromPath($row, $oldIdentifierPath, $newIdentifierPath)
            );
        }
    }

    /**
     * @param string $oldFile
     * @param string $newFile
     */
    public function renameSysFile($oldFile, $newFile)
    {
        $oldIdentifier = $this->getIdentifierFromAbsolutePath($oldFile);
        $newIdentifier = $this->getIdentifierFromAbsolutePath($newFile);
        $rows = $this->databaseConnection->exec_SELECTgetRows(
            'uid',
            self::TABLE_NAME,
            'identifier = "' . $oldIdentifier . '"'
        );
        foreach ($rows as $row) {
            $this->databaseConnection->exec_UPDATEquery(
                self::TABLE_NAME,
                'uid=' . (int)$row['uid'],
                $this->getArgumentsForSysFileFromFile($newIdentifier)
            );
        }
    }

    /**
     * Build array with values for a sys_file update
     *
     * @param array $row
     * @param string $oldIdentifierPath
     * @param string $newIdentifierPath
     * @return array
     */
    protected function getArgumentsForSysFileFromPath(array $row, $oldIdentifierPath, $newIdentifierPath)
    {
        $identifier = $row['identifier'];
        return array(
            'identifier' => str_replace($oldIdentifierPath, $newIdentifierPath, $identifier),
            'identifier_hash' => sha1($identifier),
            'folder_hash' => sha1($newIdentifierPath)
        );
    }

    /**
     * Build array with values for a sys_file update
     *
     * @param string $newIdentifier
     * @return array
     */
    protected function getArgumentsForSysFileFromFile($newIdentifier)
    {
        return array(
            'identifier' => $newIdentifier,
            'identifier_hash' => sha1($newIdentifier),
            'folder_hash' => sha1($this->getPathFromPathAndFilename($newIdentifier)),
            'name' => $this->getFilenameFromPathAndFilename($newIdentifier)
        );
    }

    /**
     * 1) Change absolute path to relative path
     * 2) substitute first folder (e.g. fileadmin)
     *
     * @param string $path
     * @return string
     */
    protected function getIdentifierFromAbsolutePath($path)
    {
        $path = $this->changeAbsoluteToRelativePath($path);
        $path = $this->removeFirstFolderFromPath($path);
        return $path;
    }

    /**
     * @param string $path
     * @return string
     */
    protected function changeAbsoluteToRelativePath($path)
    {
        return str_replace(PATH_site, '', $path);
    }
}
