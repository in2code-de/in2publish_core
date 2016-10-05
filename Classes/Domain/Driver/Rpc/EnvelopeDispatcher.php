<?php
namespace In2code\In2publishCore\Domain\Driver\Rpc;

/***************************************************************
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
 ***************************************************************/

use TYPO3\CMS\Core\Resource\Driver\DriverInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Class EnvelopeDispatcher
 */
class EnvelopeDispatcher
{
    const CMD_FOLDER_EXISTS = 'folderExists';
    const CMD_FILE_EXISTS = 'fileExists';
    const CMD_GET_PERMISSIONS = 'getPermissions';
    const CMD_GET_FOLDERS_IN_FOLDER = 'getFoldersInFolder';
    const CMD_GET_FILES_IN_FOLDER = 'getFilesInFolder';
    const CMD_GET_FILE_INFO_BY_IDENTIFIER = 'getFileInfoByIdentifier';
    const CMD_GET_HASH = 'hash';
    const CMD_CREATE_FOLDER = 'createFolder';
    const CMD_DELETE_FOLDER = 'deleteFolder';
    const CMD_DELETE_FILE = 'deleteFile';
    const CMD_ADD_FILE = 'addFile';
    const CMD_REPLACE_FILE = 'replaceFile';
    const CMD_RENAME_FILE = 'renameFile';

    /**
     * @param Envelope $envelope
     * @return bool
     */
    public function dispatch(Envelope $envelope)
    {
        $command = $envelope->getCommand();
        if (method_exists($this, $command)) {
            $envelope->setResponse(call_user_func(array($this, $command), $envelope->getRequest()));
            return true;
        }

        return false;
    }

    /**
     * @param array $request
     * @return bool
     */
    protected function folderExists(array $request)
    {
        return ResourceFactory
            ::getInstance()
            ->getStorageObject($request['storage'])
            ->hasFolder($request['folderIdentifier']);
    }

    /**
     * @param array $request
     * @return array
     */
    protected function getPermissions(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        return $this->getStorageDriver($storage)->getPermissions($request['identifier']);
    }

    /**
     * @param array $request
     * @return array
     */
    protected function getFoldersInFolder(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array(array($driver, 'getFoldersInFolder'), $request);
    }

    /**
     * @param array $request
     * @return bool
     */
    protected function fileExists(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        return $this->getStorageDriver($storage)->fileExists($request['fileIdentifier']);
    }

    /**
     * @param array $request
     * @return array
     */
    protected function getFilesInFolder(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array(array($driver, 'getFilesInFolder'), $request);
    }

    /**
     * @param array $request
     * @return array
     */
    protected function getFileInfoByIdentifier(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array(array($driver, 'getFileInfoByIdentifier'), $request);
    }

    /**
     * @param array $request
     * @return array
     */
    protected function hash(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array(array($driver, 'hash'), $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function createFolder(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array(array($driver, 'createFolder'), $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function deleteFolder(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array(array($driver, 'deleteFolder'), $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function deleteFile(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array(array($driver, 'deleteFile'), $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function addFile(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array(array($driver, 'addFile'), $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function replaceFile(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array(array($driver, 'replaceFile'), $request);
    }

    /**
     * @param array $request
     * @return mixed
     */
    protected function renameFile(array $request)
    {
        $storage = ResourceFactory::getInstance()->getStorageObject($request['storage']);
        unset($request['storage']);
        $driver = $this->getStorageDriver($storage);
        return call_user_func_array(array($driver, 'renameFile'), $request);
    }

    /**
     * @param ResourceStorage $storage
     * @return DriverInterface
     */
    protected function getStorageDriver(ResourceStorage $storage)
    {
        $driverReflection = new \ReflectionProperty(get_class($storage), 'driver');
        $driverReflection->setAccessible(true);
        /** @var DriverInterface $driver */
        return $driverReflection->getValue($storage);
    }
}
