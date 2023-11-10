<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FileInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\MissingFileInfo;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

class FileInfoService
{
    protected const PROPERTIES = [
        'size',
        'mimetype',
        'name',
        'extension',
        'folder_hash',
        'identifier',
        'storage',
        'identifier_hash',
    ];

    public function getFileInfo(DriverInterface $driver, int $storage, string $fileIdentifier): FilesystemInfo
    {
        if (!$driver->fileExists($fileIdentifier)) {
            return new MissingFileInfo($storage, $fileIdentifier);
        }

        $fileInfo = $driver->getFileInfoByIdentifier($fileIdentifier, self::PROPERTIES);
        $fileInfo['folderHash'] = $fileInfo['folder_hash'];
        $fileInfo['identifierHash'] = $fileInfo['identifier_hash'];
        unset($fileInfo['folder_hash'], $fileInfo['identifier_hash']);
        $fileInfo['sha1'] = $driver->hash($fileIdentifier, 'sha1');
        $fileInfo['publicUrl'] = $driver->getPublicUrl($fileInfo['identifier']);
        return new FileInfo(...$fileInfo);
    }
}
