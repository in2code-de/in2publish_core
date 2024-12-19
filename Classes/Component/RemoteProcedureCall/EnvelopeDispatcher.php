<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RemoteProcedureCall;

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

use In2code\In2publishCore\CommonInjection\ResourceFactoryInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInformationCollection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFileInfoServiceInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\LocalFolderInfoServiceInjection;
use In2code\In2publishCore\Component\RemoteProcedureCall\Exception\StorageIsOfflineException;
use ReflectionProperty;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

use function get_class;
use function method_exists;

class EnvelopeDispatcher
{
    use ResourceFactoryInjection;
    use LocalFolderInfoServiceInjection;
    use LocalFileInfoServiceInjection;

    public const CMD_GET_FOLDER_INFO = 'getFolderInfo';
    public const CMD_GET_FILE_INFO = 'getFileInfo';
    public const CMD_FILE_EXISTS = 'fileExists';

    public function dispatch(Envelope $envelope): bool
    {
        $command = $envelope->getCommand();
        if (method_exists($this, $command)) {
            $request = $envelope->getRequest();
            $response = $this->$command($request);
            $envelope->setResponse($response);
            return true;
        }

        return false;
    }

    protected function getFolderInfo(array $request): FilesystemInformationCollection
    {
        return $this->localFolderInfoService->getFolderInfo($request);
    }

    public function getFileInfo(array $request): FilesystemInformationCollection
    {
        return $this->localFileInfoService->getFileInfo($request);
    }

    protected function fileExists(array $request): bool
    {
        return $this->getStorageDriver($request)->fileExists($request['fileIdentifier']);
    }

    protected function getStorageDriver(array $request): DriverInterface
    {
        $storage = $this->resourceFactory->getStorageObject($request['storage']);
        if (!$storage->isOnline()) {
            throw new StorageIsOfflineException((int)$request['storage'], 4307401617);
        }
        $driverReflection = new ReflectionProperty(get_class($storage), 'driver');
        $driverReflection->setAccessible(true);
        /** @var DriverInterface $driver */
        return $driverReflection->getValue($storage);
    }
}
