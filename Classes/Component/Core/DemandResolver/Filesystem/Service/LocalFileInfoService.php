<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInformationCollection;

class LocalFileInfoService
{
    use FalDriverServiceInjection;
    use FileInfoServiceInjection;

    public function getFileInfo(array $request): FilesystemInformationCollection
    {
        $collection = new FilesystemInformationCollection();

        foreach ($request as $storage => $fileIdentifiers) {
            $driver = $this->falDriverService->getDriver($storage);
            foreach ($fileIdentifiers as $identifier) {
                $collection->addFilesystemInfo($this->fileInfoService->getFileInfo($driver, $storage, $identifier));
            }
        }

        return $collection;
    }
}
