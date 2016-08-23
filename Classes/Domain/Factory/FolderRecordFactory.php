<?php
namespace In2code\In2publishCore\Domain\Factory;

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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Security\SshConnection;
use In2code\In2publishCore\Utility\DatabaseUtility;
use In2code\In2publishCore\Utility\FolderUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class FolderRecordFactory
 */
class FolderRecordFactory
{
    const TABLE_NAME_PHYSICAL_FOLDER = 'physical_folder';
    const TABLE_NAME_PHYSICAL_FILE = 'physical_file';

    /**
     * @var SshConnection
     */
    protected $sshConnection = null;

    /**
     * @var CommonRepository
     */
    protected $commonRepository = null;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * FolderRecordFactory constructor.
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
        $this->sshConnection = SshConnection::makeInstance();
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $this->commonRepository = $objectManager->get(
            'In2code\\In2publishCore\\Domain\\Repository\\CommonRepository',
            DatabaseUtility::buildLocalDatabaseConnection(),
            DatabaseUtility::buildForeignDatabaseConnection(),
            'sys_file'
        );
    }

    /**
     * @param string $folderIdentifier
     * @return Record
     */
    public function makeInstance($folderIdentifier)
    {
        $record = $this->createFolderRecordInstance($folderIdentifier);
        $record = $this->setRelatedPhysicalFolders($record);
        $record = $this->setRelatedDatabaseFiles($record);
        $record = $this->setRelatedPhysicalFiles($record);
        $record->sortRelatedRecords(
            'sys_file',
            function ($recordA, $recordB) {
                /** @var Record $recordA */
                /** @var Record $recordB */
                return strcmp(
                    $recordA->getMergedProperty('name'),
                    $recordB->getMergedProperty('name')
                );
            }
        );
        return $record;
    }

    /**
     * @param Record $record
     * @return Record
     */
    protected function setRelatedPhysicalFolders(Record $record)
    {
        $relativePath = $record->getLocalProperty('relativePath');
        $localFolders = FolderUtility::getFoldersInFolder($relativePath);
        $foreignFolders = $this->sshConnection->getFoldersInRemoteFolder($relativePath);

        $mergedFolders = array();
        foreach ($localFolders as $localFolder) {
            $mergedFolders[$localFolder['uid']]['local'] = $localFolder;
        }
        foreach ($foreignFolders as $foreignFolder) {
            $mergedFolders[$foreignFolder['uid']]['foreign'] = $foreignFolder;
        }
        foreach ($mergedFolders as $mergedFolder) {
            $record->addRelatedRecord($this->createFolderRecordInstanceFromMergedFolder($mergedFolder));
        }
        return $record;
    }

    /**
     * @param array $mergedFolder
     * @return Record
     */
    protected function createFolderRecordInstanceFromMergedFolder(array $mergedFolder)
    {
        $localProperties = array();
        if (!empty($mergedFolder['local'])) {
            $localProperties = $mergedFolder['local'];
        }
        $foreignProperties = array();
        if (!empty($mergedFolder['foreign'])) {
            $foreignProperties = $mergedFolder['foreign'];
        }
        return GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Model\\Record',
            self::TABLE_NAME_PHYSICAL_FOLDER,
            $localProperties,
            $foreignProperties,
            array(),
            array()
        );
    }

    /**
     * @param Record $record
     * @return Record
     */
    protected function setRelatedDatabaseFiles(Record $record)
    {
        $relativePath = $record->getLocalProperty('relativePath');
        if (strpos($relativePath, 'fileadmin') === 0) {
            $relativePath = substr($relativePath, 9);
        }
        $relativePath = rtrim($relativePath, '/');
        if ($relativePath === '') {
            $relativePath = '/';
        }
        foreach ($this->commonRepository->findByProperty('folder_hash', sha1($relativePath)) as $relatedRecord) {
            $record->addRelatedRecord($relatedRecord);
        }
        return $record;
    }

    /**
     * @param Record $record
     * @return Record
     */
    protected function setRelatedPhysicalFiles(Record $record)
    {
        $relativePath = $record->getLocalProperty('relativePath');
        $localFiles = FolderUtility::getFilesInFolder($relativePath);
        $foreignFiles = $this->sshConnection->getFilesInRemoteFolder($relativePath);
        $mergedFiles = $this->initializeMergedFiles($localFiles);
        $mergedFiles = $this->enrichMergedFilesWithForeignFiles($mergedFiles, $foreignFiles);

        $mergedFiles = $this->setHasSysFilesForPhysicalFiles($mergedFiles, $record);

        foreach ($mergedFiles as $mergedFile) {
            $record->addRelatedRecord($this->createFileRecordInstanceFromMergedFile($mergedFile));
        }
        return $record;
    }

    /**
     * Enrich merged files with sys files
     *
     * @param array $mergedFiles
     * @param Record $record
     * @return array
     */
    protected function setHasSysFilesForPhysicalFiles(array $mergedFiles, Record $record)
    {
        $relatedRecords = $record->getRelatedRecords();
        if (!empty($relatedRecords['sys_file'])) {
            /** @var Record $sysFile */
            foreach ($relatedRecords['sys_file'] as $sysFile) {
                $sides = array(
                    'local' => $sysFile->getLocalProperty('name'),
                    'foreign' => $sysFile->getForeignProperty('name'),
                );
                foreach ($sides as $side => $sysFileName) {
                    if (empty($sysFileName)) {
                        continue;
                    }
                    foreach ($mergedFiles as $key => $mergedFile) {
                        if (empty($mergedFile[$side]) || true === $mergedFiles[$key]['has_sys_file']) {
                            continue;
                        }
                        if ($sysFileName === $mergedFile[$side]['name']) {
                            $mergedFiles[$key]['has_sys_file'] = true;
                            if (!$sysFile->isChanged()) {
                                $reverseSide = ($side === 'local' ? 'foreign' : $side);
                                if (isset($mergedFile[$side]) && isset($mergedFile[$reverseSide])) {
                                    if (empty($mergedFile['local']) && !empty($mergedFile['foreign'])) {
                                        $sysFile->setState(RecordInterface::RECORD_STATE_DELETED);
                                    } elseif (!empty($mergedFile['local']) && empty($mergedFile['foreign'])) {
                                        $sysFile->setState(RecordInterface::RECORD_STATE_ADDED);
                                    } elseif (isset($mergedFile[$side]['hash'])
                                              && isset($mergedFile[$reverseSide]['hash'])
                                              && $mergedFile[$side]['hash'] !== $mergedFile[$reverseSide]['hash']
                                    ) {
                                        $sysFile->setState(RecordInterface::RECORD_STATE_CHANGED);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $mergedFiles;
    }

    /**
     * Create mergeFiles array out of local files
     *      array(
     *          89787079498 => array(
     *              has_sys_file => false,
     *              local => array(...),
     *              foreign => array()
     *          )
     *      )
     *
     * @param array $localFiles
     * @return array
     */
    protected function initializeMergedFiles(array $localFiles)
    {
        $mergedFiles = array();
        foreach ($localFiles as $localFile) {
            $mergedFiles[$localFile['uid']]['has_sys_file'] = false;
            $mergedFiles[$localFile['uid']]['local'] = $localFile;
            $mergedFiles[$localFile['uid']]['foreign'] = array();
        }
        return $mergedFiles;
    }

    /**
     * Enrich merged files with foreign files
     *
     * @param array $mergedFiles
     * @param array $foreignFiles
     * @return array
     */
    protected function enrichMergedFilesWithForeignFiles(array $mergedFiles, array $foreignFiles)
    {
        foreach ($foreignFiles as $foreignFile) {
            $mergedFiles[$foreignFile['uid']]['has_sys_file'] = false;
            $mergedFiles[$foreignFile['uid']]['foreign'] = $foreignFile;
            if (!isset($mergedFiles[$foreignFile['uid']]['local'])) {
                $mergedFiles[$foreignFile['uid']]['local'] = array();
            }
        }
        return $mergedFiles;
    }

    /**
     * @param Record $record
     * @return Record
     */
    public function addInformationAboutPhysicalFile(Record $record)
    {
        $identifier = null;
        if ($record->localRecordExists()) {
            $identifier = $record->getLocalProperty('identifier');
            $localName = $record->getLocalProperty('name');
        } else {
            $localName = null;
        }
        if ($record->foreignRecordExists()) {
            $identifier = $record->getForeignProperty('identifier');
            $foreignName = $record->getLocalProperty('name');
        } else {
            $foreignName = null;
        }
        if ($identifier === null) {
            $this->logger->error(
                'Tried to set physical information for a record but it neither exists local nor on foreign',
                array(
                    'record' => $record,
                )
            );
            return $record;
        }
        $relativePath = 'fileadmin' . dirname($identifier) . DIRECTORY_SEPARATOR;
        $localPhysicalFile = null;
        if ($localName !== null) {
            $localFiles = FolderUtility::getFilesInFolder($relativePath);
            foreach ($localFiles as $localFile) {
                if ($localFile['name'] === $localName) {
                    $localPhysicalFile = $localFile;
                    break;
                }
            }
        }
        $foreignPhysicalFile = null;
        if ($foreignName !== null) {
            $foreignFiles = $this->sshConnection->getFilesInRemoteFolder($relativePath);
            foreach ($foreignFiles as $foreignFile) {
                if ($foreignFile['name'] === $foreignName) {
                    $foreignPhysicalFile = $foreignFile;
                    break;
                }
            }
        }

        // if the local file does not exist, but the remote file does, the record has to be marked as deleted
        if ((null === $localPhysicalFile && null !== $foreignPhysicalFile)
            && (empty($localPhysicalFile['hash']) && !empty($foreignPhysicalFile['hash']))
        ) {
            $record->setState(RecordInterface::RECORD_STATE_DELETED);
        } elseif (($localPhysicalFile === null || $foreignPhysicalFile === null)
                  || ($localPhysicalFile['hash'] !== $foreignPhysicalFile['hash'])
        ) {
            $record->setState(RecordInterface::RECORD_STATE_CHANGED);
        }
        return $record;
    }

    /**
     * @param array $mergedFile
     * @return Record
     */
    protected function createFileRecordInstanceFromMergedFile(array $mergedFile)
    {
        $localProperties = array();
        if (!empty($mergedFile['local'])) {
            $localProperties = $mergedFile['local'];
        }
        $foreignProperties = array();
        if (!empty($mergedFile['foreign'])) {
            $foreignProperties = $mergedFile['foreign'];
        }
        $additionalProperties = array(
            'hasSysFile' => $mergedFile['has_sys_file'],
            'identifier' =>
                (!empty($mergedFile['local']['identifier'])
                    ?
                    $mergedFile['local']['identifier']
                    :
                    $mergedFile['foreign']['identifier']
                ),
        );
        return GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Model\\Record',
            self::TABLE_NAME_PHYSICAL_FILE,
            $localProperties,
            $foreignProperties,
            array(),
            $additionalProperties
        );
    }

    /**
     * @param string $folderIdentifier
     * @return Record
     */
    protected function createFolderRecordInstance($folderIdentifier)
    {
        $localProperties = FolderUtility::getFolderInformation($folderIdentifier);
        $foreignProperties = $this->sshConnection->getRemoteFolderInformation($folderIdentifier);
        $additionalProperties = array(
            'absoluteDepth' => count(GeneralUtility::trimExplode('/', $folderIdentifier, true)),
        );
        return GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Model\\Record',
            self::TABLE_NAME_PHYSICAL_FOLDER,
            $localProperties,
            $foreignProperties,
            array(),
            $additionalProperties
        );
    }
}
