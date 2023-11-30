<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

use In2code\In2publishCore\CommonInjection\ResourceFactoryInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInformationCollection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FolderInfo;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\MissingFolderInfo;
use TYPO3\CMS\Core\Utility\PathUtility;

class LocalFolderInfoService
{
    use FalDriverServiceInjection;
    use ResourceFactoryInjection;
    use FileInfoServiceInjection;
    use SharedFilesystemInfoCacheInjection;

    public function getFolderInfo(array $request): FilesystemInformationCollection
    {
        $collection = $this->sharedFilesystemInfoCache->getLocal($request);
        if (null !== $collection) {
            return $collection;
        }
        $collection = new FilesystemInformationCollection();

        foreach ($request as $storage => $identifiers) {
            $driver = $this->falDriverService->getDriver($storage);
            foreach ($identifiers as $identifier) {
                if (!$driver->folderExists($identifier)) {
                    $collection->addFilesystemInfo(new MissingFolderInfo($storage, $identifier));
                    continue;
                }

                $name = $this->getFolderName($identifier, $storage);
                $folderInformation = new FolderInfo($storage, $identifier, $name);
                $collection->addFilesystemInfo($folderInformation);

                $folderIdentifiers = $driver->getFoldersInFolder($identifier);
                foreach ($folderIdentifiers as $folderIdentifier) {
                    $name = $this->getFolderName($folderIdentifier, $storage);
                    $childFolderInformation = new FolderInfo($storage, $folderIdentifier, $name);
                    $folderInformation->addFolder($childFolderInformation);
                }

                $fileIdentifiers = $driver->getFilesInFolder($identifier);
                foreach ($fileIdentifiers as $fileIdentifier) {
                    $fileInfo = $this->fileInfoService->getFileInfo($driver, $storage, $fileIdentifier);
                    $folderInformation->addFile($fileInfo);
                }
            }
        }

        $this->sharedFilesystemInfoCache->setLocal($collection, $request);

        return $collection;
    }

    public function getFolderName(string $identifier, int $storage): string
    {
        $name = PathUtility::basename($identifier);
        if (empty($name)) {
            $name = $this->resourceFactory->getStorageObject($storage)->getName();
        }
        return $name;
    }
}
