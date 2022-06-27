<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\FileHandling\Service;

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
            $foundFile = $driver->getFileInfoByIdentifier($fileIdentifier, self::PROPERTIES);
            $publicUrl = $driver->getPublicUrl($foundFile['identifier']);
            // TODO: If the publicUrl does not contain the host we need to add it here
            $foundFile['publicUrl'] = $publicUrl;
            $files[] = $foundFile;
        }
        return [
            'folders' => $folders,
            'files' => $files,
        ];
    }
}
