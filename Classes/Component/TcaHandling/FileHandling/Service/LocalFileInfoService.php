<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\FileHandling\Service;

use InvalidArgumentException;
use TYPO3\CMS\Core\Database\Connection;

use function array_keys;
use function hash;

class LocalFileInfoService
{
    protected const PROPERTIES = [
        'size',
        'mimetype',
        'name',
        'extension',
        'folder_hash',
    ];
    protected FalDriverService $falDriverService;
    protected Connection $localDatabase;

    public function injectFalDriverService(FalDriverService $falDriverService): void
    {
        $this->falDriverService = $falDriverService;
    }

    public function injectLocalDatabase(Connection $localDatabase): void
    {
        $this->localDatabase = $localDatabase;
    }

    public function addFileInfoToFiles(array $files): array
    {
        $storages = array_keys($files);
        $drivers = $this->falDriverService->getDrivers($storages);

        foreach ($files as $storage => $identifiers) {
            foreach (array_keys($identifiers) as $identifier) {
                try {
                    $driver = $drivers[$storage];
                    $fileInfo = $driver->getFileInfoByIdentifier($identifier, self::PROPERTIES);

                    $files[$storage][$identifier]['props'] = [
                        'storage' => $storage,
                        'identifier' => $identifier,
                        'identifier_hash' => hash('sha1', $identifier),
                        'size' => $fileInfo['size'],
                        'mimetype' => $fileInfo['mimetype'],
                        'name' => $fileInfo['name'],
                        'extension' => $fileInfo['extension'],
                        'folder_hash' => $fileInfo['folder_hash'],
                    ];
                } catch (InvalidArgumentException $exception) {
                }
            }
        }
        return $files;
    }
}
