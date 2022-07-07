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

use In2code\In2publishCore\Component\RemoteProcedureCall\Exception\StorageIsOfflineException;
use In2code\In2publishCore\Component\TcaHandling\FileHandling\Service\FileSystemInfoService;
use InvalidArgumentException;
use ReflectionProperty;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;

use function get_class;
use function method_exists;

class EnvelopeDispatcher
{
    public const CMD_FOLDER_EXISTS = 'folderExists';
    public const CMD_FILE_EXISTS = 'fileExists';
    public const CMD_LIST_FOLDER_CONTENTS = 'listFolderContents';
    /**
     * Limits the amount of files in a folder for pre-fetching. If there are more than $prefetchLimit files in
     * the selected folder they will not be processed when not requested explicitly.
     */
    private ResourceFactory $resourceFactory;
    private FileSystemInfoService $fileSystemEnumerationService;

    public function injectResourceFactory(ResourceFactory $resourceFactory): void
    {
        $this->resourceFactory = $resourceFactory;
    }

    public function injectFileSystemEnumerationService(FileSystemInfoService $fileSystemEnumerationService): void
    {
        $this->fileSystemEnumerationService = $fileSystemEnumerationService;
    }

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

    protected function folderExists(array $request): bool
    {
        return $this->getStorageDriver($request)->folderExists($request['folderIdentifier']);
    }

    protected function fileExists(array $request): bool
    {
        return $this->getStorageDriver($request)->fileExists($request['fileIdentifier']);
    }

    public function listFolderContents(array $request): array
    {
        $storageUid = $request['storageUid'];
        $identifier = $request['identifier'];
        try {
            return $this->fileSystemEnumerationService->listFolderContents($storageUid, $identifier);
        } catch (InvalidArgumentException $e) {
            return [];
        }
    }

    protected function getStorageDriver(array $request): DriverInterface
    {
        $storage = $this->resourceFactory->getStorageObject($request['storage']);
        if (!$storage->isOnline()) {
            throw new StorageIsOfflineException((int)$request['storage']);
        }
        $driverReflection = new ReflectionProperty(get_class($storage), 'driver');
        $driverReflection->setAccessible(true);
        /** @var DriverInterface $driver */
        return $driverReflection->getValue($storage);
    }
}
