<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Factory;

/*
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Service\Database\UidReservationService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use LogicException;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use function array_intersect_key;
use function explode;
use function strtolower;
use function time;

/**
 * Class FileIndexFactory
 */
class FileIndexFactory
{
    /**
     * @var DriverInterface
     */
    protected $localDriver = null;

    /**
     * @var DriverInterface
     */
    protected $foreignDriver = null;

    /**
     * @var array
     */
    protected $sysFileTca = [];

    /**
     * @var ContextService
     */
    protected $contextService;

    /**
     * FileIndexFactory constructor.
     *
     * @param DriverInterface $localDriver
     * @param DriverInterface $foreignDriver
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct(DriverInterface $localDriver, DriverInterface $foreignDriver)
    {
        $this->localDriver = $localDriver;
        $this->foreignDriver = $foreignDriver;
        $this->sysFileTca = GeneralUtility::makeInstance(TcaService::class)
                                          ->getConfigurationArrayForTable('sys_file');
        $this->contextService = GeneralUtility::makeInstance(ContextService::class);
    }

    /**
     * @param string $side
     * @param string $identifier
     *
     * @return RecordInterface
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function makeInstanceForSide($side, $identifier): RecordInterface
    {
        $foreignProperties = [];
        $localProperties = [];
        $uid = 0;

        if ('both' === $side || 'local' === $side) {
            $localProperties = $this->getFileIndexArray($identifier, 'local');
            $uid = $localProperties['uid'];
        }

        if ('both' === $side || 'foreign' === $side) {
            $foreignProperties = $this->getFileIndexArray($identifier, 'foreign', $uid);
        }

        return GeneralUtility::makeInstance(
            Record::class,
            'sys_file',
            $localProperties,
            $foreignProperties,
            $this->sysFileTca,
            []
        );
    }

    /**
     * @param RecordInterface $record
     * @param string $identifier
     * @param string $side
     * @param bool $clearOpposite Set to true if you want to remove all properties from the "opposite" side
     */
    public function updateFileIndexInfoBySide(RecordInterface $record, $identifier, $side, $clearOpposite = false)
    {
        $oppositeSide = ($side === 'local' ? 'foreign' : 'local');
        $uid = $record->getPropertyBySideIdentifier($oppositeSide, 'uid');

        $record->setPropertiesBySideIdentifier($side, $this->getFileIndexArray($identifier, $side, $uid));

        if ($clearOpposite) {
            $record->setPropertiesBySideIdentifier($oppositeSide, []);
        }

        $record->setDirtyProperties()->calculateState();
    }

    /**
     * @param RecordInterface $record
     * @param string $localIdentifier
     * @param string $foreignIdentifier
     */
    public function updateFileIndexInfo(RecordInterface $record, $localIdentifier, $foreignIdentifier)
    {
        $uid = $record->getIdentifier();
        $record->addAdditionalProperty('recordDatabaseState', $record->getState());
        $localFileInfo = $this->getFileIndexArray($localIdentifier, 'local', $uid);
        $foreignFileInfo = $this->getFileIndexArray($foreignIdentifier, 'foreign', $uid);
        // only set new file info if the file exists at least on one side, else we only deal with the database record
        if (!empty($localFileInfo) || !empty($foreignFileInfo)) {
            $record->setLocalProperties($localFileInfo);
            $record->setForeignProperties($foreignFileInfo);
            $record->setDirtyProperties()->calculateState();
        }
    }

    /**
     * This method is mostly a copy of an indexer method
     *
     * @param string $identifier
     * @param $side
     * @param int $uid Predefined UID
     * @return array
     * @see \TYPO3\CMS\Core\Resource\Index\Indexer::gatherFileInformationArray
     *
     */
    public function getFileIndexArray($identifier, $side, $uid = 0): array
    {
        $fileInfo = $this->getDriverSpecificFileInfo($identifier, $side);

        if (empty($fileInfo)) {
            return $fileInfo;
        }

        $remapKeys = [
            'mtime' => 'modification_date',
            'ctime' => 'creation_date',
            'mimetype' => 'mime_type',
        ];

        foreach ($remapKeys as $fileInfoKey => $sysFileRecordKey) {
            $fileInfo[$sysFileRecordKey] = $fileInfo[$fileInfoKey];
            unset($fileInfo[$fileInfoKey]);
        }

        $fileInfo['type'] = $this->determineFileType($fileInfo);
        $fileInfo['extension'] = PathUtility::pathinfo($fileInfo['name'], PATHINFO_EXTENSION);
        $fileInfo['missing'] = 0;
        $fileInfo['last_indexed'] = 0;
        $fileInfo['metadata'] = 0;
        $fileInfo['tstamp'] = time();
        $fileInfo['pid'] = 0;

        if ($uid <= 0 && $this->contextService->isLocal()) {
            $fileInfo['uid'] = $this->getUidReservationService()->getReservedUid();
        } else {
            $fileInfo['uid'] = $uid;
        }
        $uid = (int)$fileInfo['uid'];

        $fileInfo = array_intersect_key(
            $fileInfo,
            [
                'uid' => '',
                'pid' => '',
                'missing' => '',
                'type' => '',
                'storage' => '',
                'identifier' => '',
                'identifier_hash' => '',
                'extension' => '',
                'mime_type' => '',
                'name' => '',
                'sha1' => '',
                'size' => '',
                'creation_date' => '',
                'modification_date' => '',
                'folder_hash' => '',
                'tstamp' => '',
            ]
        );

        // convert all values to string to match the resulting types of a database select query result
        foreach ($fileInfo as $index => $value) {
            $fileInfo[$index] = (string)$value;
        }

        if ($this->contextService->isLocal()) {
            $databaseConnection = DatabaseUtility::buildDatabaseConnectionForSide($side);

            if (0 === $count = $databaseConnection->count('uid', 'sys_file', ['uid' => $uid])) {
                $databaseConnection->insert('sys_file', $fileInfo);
            } elseif ($count > 0) {
                $databaseConnection->update('sys_file', ['uid' => $uid], $fileInfo);
            }
        }

        return $fileInfo;
    }

    /**
     * Adapted copy of
     *
     * @param array $fileInfo
     * @return int
     * @see \TYPO3\CMS\Core\Resource\Index\Indexer::getFileType
     *
     */
    protected function determineFileType(array $fileInfo): int
    {
        list($fileType) = explode('/', $fileInfo['mime_type']);
        switch (strtolower($fileType)) {
            case 'text':
                $type = File::FILETYPE_TEXT;
                break;
            case 'image':
                $type = File::FILETYPE_IMAGE;
                break;
            case 'audio':
                $type = File::FILETYPE_AUDIO;
                break;
            case 'video':
                $type = File::FILETYPE_VIDEO;
                break;
            case 'application':
            case 'software':
                $type = File::FILETYPE_APPLICATION;
                break;
            default:
                $type = File::FILETYPE_UNKNOWN;
        }
        return $type;
    }

    /**
     * @param string $identifier
     * @param string $side
     * @return array
     */
    protected function getDriverSpecificFileInfo($identifier, $side): array
    {
        if ($side === 'local') {
            $driver = $this->localDriver;
        } elseif ($side === 'foreign') {
            $driver = $this->foreignDriver;
        } else {
            throw new LogicException('Unsupported side "' . $side . '"', 1476106674);
        }

        if ($driver->fileExists($identifier)) {
            $fileInfo = $driver->getFileInfoByIdentifier($identifier);
            unset($fileInfo['atime']);
            $fileInfo['sha1'] = $driver->hash($identifier, 'sha1');
            return $fileInfo;
        }
        return [];
    }

    /**
     * @return UidReservationService
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    protected function getUidReservationService(): UidReservationService
    {
        return GeneralUtility::makeInstance(UidReservationService::class);
    }
}
