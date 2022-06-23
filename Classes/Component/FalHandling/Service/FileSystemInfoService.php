<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\FalHandling\Service;

use In2code\In2publishCore\Component\TcaHandling\FileHandling\Service\FalDriverService;

class FileSystemInfoService
{
    protected const PROPERTIES = [
        'size',
        'mimetype',
        'name',
        'extension',
        'folder_hash',
        'identifier',
        'storage',
    ];
    protected FalDriverService $falDriverService;

    public function injectFalDriverService(FalDriverService $falDriverService): void
    {
        $this->falDriverService = $falDriverService;
    }

    public function listFolderContents(int $storageUid, string $identifier): array
    {
        $driver = $this->falDriverService->getDriver($storageUid);

        $folders = $driver->getFoldersInFolder($identifier);
        $fileIdentifiers = $driver->getFilesInFolder($identifier);
        $files = [];
        foreach ($fileIdentifiers as $fileIdentifier) {
            $files[] = $driver->getFileInfoByIdentifier($fileIdentifier, self::PROPERTIES);
        }
        return [
            'folders' => $folders,
            'files' => $files,
        ];
    }
}
