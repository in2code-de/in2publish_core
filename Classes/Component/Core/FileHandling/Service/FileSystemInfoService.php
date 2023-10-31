<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling\Service;

use InvalidArgumentException;
use Throwable;

class FileSystemInfoService
{
    use FalDriverServiceInjection;

    protected const PROPERTIES = [
        'size',
        'mimetype',
        'name',
        'extension',
        'folder_hash',
        'identifier',
        'storage',
    ];

    public function listFolderContents(int $storageUid, string $identifier): array
    {
        $driver = $this->falDriverService->getDriver($storageUid);

        try {
            $folders = $driver->getFoldersInFolder($identifier);
            $fileIdentifiers = $driver->getFilesInFolder($identifier);
        } catch (InvalidArgumentException $exception) {
            return [];
        }
        $files = $this->getFileInfo([$storageUid => $fileIdentifiers]);
        return [
            'folders' => $folders,
            'files' => $files,
        ];
    }

    /**
     * @param array<int, array<string>> $files
     */
    public function getFileInfo(array $files): array
    {
        $info = [];

        foreach ($files as $storage => $fileIdentifiers) {
            $driver = $this->falDriverService->getDriver($storage);
            foreach ($fileIdentifiers as $fileIdentifier) {
                try {
                    $fileInfo = $driver->getFileInfoByIdentifier($fileIdentifier, self::PROPERTIES);
                    $fileInfo['sha1'] = $driver->hash($fileIdentifier, 'sha1');
                    $fileInfo['publicUrl'] = $driver->getPublicUrl($fileInfo['identifier']);
                    $info[$storage][$fileIdentifier] = $fileInfo;
                } catch (Throwable $exception) {
                    $info[$storage][$fileIdentifier] = null;
                }
            }
        }

        return $info;
    }
}
