<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling\Service;

use InvalidArgumentException;

use function array_keys;

class LocalFileInfoService
{
    public const PROPS_TO_EXTRACT = [
        'size',
        'mimetype',
        'name',
        'extension',
        'folder_hash',
    ];
    protected FalDriverService $falDriverService;

    public function injectFalDriverService(FalDriverService $falDriverService): void
    {
        $this->falDriverService = $falDriverService;
    }

    public function getFileInfo(array $files): array
    {
        $storages = array_keys($files);
        $drivers = $this->falDriverService->getDrivers($storages);

        $fileInfoArray = [];

        foreach ($files as $storage => $identifiers) {
            $driver = $drivers[$storage];
            foreach ($identifiers as $identifier) {
                try {
                    $fileInfoArray[$storage][$identifier] = $driver->getFileInfoByIdentifier(
                        $identifier,
                        self::PROPS_TO_EXTRACT
                    );
                } catch (InvalidArgumentException $exception) {
                    $fileInfoArray[$storage][$identifier] = null;
                }
            }
        }

        return $fileInfoArray;
    }
}
