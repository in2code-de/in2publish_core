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

use In2code\In2publishCore\Utility\DatabaseUtility;
use PDO;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use function sha1;
use function str_replace;

/**
 * Class SysFileService finds not allowed file entries in sys_file
 */
class SysFileService extends AbstractService
{
    const TABLE_NAME = 'sys_file';

    /**
     * @var Connection
     */
    protected $connection = null;

    /**
     * SysFileService constructor.
     */
    public function __construct()
    {
        $this->connection = DatabaseUtility::buildLocalDatabaseConnection();
    }

    /**
     * @param string $oldFolder
     * @param string $newFolder
     */
    public function renameFolderInSysFile($oldFolder, $newFolder)
    {
        $oldIdentifierPath = $this->getIdentifierFromAbsolutePath($oldFolder);
        $newIdentifierPath = $this->getIdentifierFromAbsolutePath($newFolder);
        $query = $this->connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $where = $query->expr()->like('identifier', $query->createNamedParameter("%$oldIdentifierPath%"));
        $rows = $query->select('uid', 'identifier')->from(self::TABLE_NAME)->where($where)->execute()->fetchAll();
        foreach ($rows as $row) {
            $this->connection->update(
                static::TABLE_NAME,
                ['uid' => (int)$row['uid']],
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

        $query = DatabaseUtility::buildLocalDatabaseConnection()->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $statement = $query->select('uid')
                           ->from(static::TABLE_NAME)
                           ->where($query->expr()->eq('identifier', $query->createNamedParameter($oldIdentifier)))
                           ->setMaxResults(1)
                           ->execute();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $this->connection->update(
                static::TABLE_NAME,
                $this->getArgumentsForSysFileFromFile($newIdentifier),
                ['uid' => (int)$row['uid']]
            );
        }
    }

    /**
     * Build array with values for a sys_file update
     *
     * @param array $row
     * @param string $oldIdentifierPath
     * @param string $newIdentifierPath
     *
     * @return array
     */
    protected function getArgumentsForSysFileFromPath(array $row, $oldIdentifierPath, $newIdentifierPath): array
    {
        $identifier = $row['identifier'];
        return [
            'identifier' => str_replace($oldIdentifierPath, $newIdentifierPath, $identifier),
            'identifier_hash' => sha1($identifier),
            'folder_hash' => sha1($newIdentifierPath),
        ];
    }

    /**
     * Build array with values for a sys_file update
     *
     * @param string $newIdentifier
     *
     * @return array
     */
    protected function getArgumentsForSysFileFromFile($newIdentifier): array
    {
        return [
            'identifier' => $newIdentifier,
            'identifier_hash' => sha1($newIdentifier),
            'folder_hash' => sha1($this->getPathFromPathAndFilename($newIdentifier)),
            'name' => $this->getFilenameFromPathAndFilename($newIdentifier),
        ];
    }

    /**
     * 1) Change absolute path to relative path
     * 2) substitute first folder (e.g. fileadmin)
     *
     * @param string $path
     *
     * @return string
     */
    protected function getIdentifierFromAbsolutePath($path): string
    {
        $path = $this->changeAbsoluteToRelativePath($path);
        $path = $this->removeFirstFolderFromPath($path);
        return $path;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function changeAbsoluteToRelativePath($path): string
    {
        return str_replace(PATH_site, '', $path);
    }
}
