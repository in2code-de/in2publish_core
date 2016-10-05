<?php
namespace In2code\In2publishCore\Domain\Service\Publishing;

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

use In2code\In2publishCore\Domain\Driver\Rpc\Envelope;
use In2code\In2publishCore\Domain\Driver\Rpc\EnvelopeDispatcher;
use In2code\In2publishCore\Domain\Driver\Rpc\Letterbox;
use In2code\In2publishCore\Security\SshConnection;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FilePublisherService
 *
 * TODO replace Letterbox/Envelope with RFALD
 */
class FilePublisherService
{
    /**
     * @var Letterbox
     */
    protected $letterbox = null;

    /**
     * FolderPublisherService constructor.
     */
    public function __construct()
    {
        $this->letterbox = GeneralUtility::makeInstance(
            'In2code\\In2publishCore\\Domain\\Driver\\Rpc\\Letterbox'
        );
    }

    /**
     * Removes a file from a foreign storage
     *
     * @param int $storage
     * @param string $fileIdentifier
     * @return bool
     */
    public function removeForeignFile($storage, $fileIdentifier)
    {
        $uid = $this->letterbox->sendEnvelope(
            new Envelope(
                EnvelopeDispatcher::CMD_DELETE_FILE,
                array('storage' => $storage, 'fileIdentifier' => $fileIdentifier)
            )
        );

        SshConnection::makeInstance()->executeRpc($uid);
        $envelope = $this->letterbox->receiveEnvelope($uid);
        return true === $envelope->getResponse();
    }

    /**
     * Adds a file to a foreign storage
     *
     * @param int $storage
     * @param string $fileIdentifier
     * @return bool
     */
    public function addFileToForeign($storage, $fileIdentifier)
    {
        $file = ResourceFactory::getInstance()->getStorageObject($storage)->getFile($fileIdentifier);
        $readablePath = $file->getForLocalProcessing(false);

        $temporaryIdentifier = SshConnection::makeInstance()->transferTemporaryFile($readablePath);

        $request = [
            'storage' => $storage,
            'localFilePath' => $temporaryIdentifier,
            'targetFolderIdentifier' => dirname($fileIdentifier),
            'newFileName' => basename($fileIdentifier),
            'removeOriginal' => true,
        ];

        $uid = $this->letterbox->sendEnvelope(new Envelope(EnvelopeDispatcher::CMD_ADD_FILE, $request));

        SshConnection::makeInstance()->executeRpc($uid);
        $envelope = $this->letterbox->receiveEnvelope($uid);
        return $fileIdentifier === $envelope->getResponse();
    }

    /**
     * @param string $storage
     * @param string $fileIdentifier
     * @return bool
     */
    public function updateFileOnForeign($storage, $fileIdentifier)
    {
        $file = ResourceFactory::getInstance()->getStorageObject($storage)->getFile($fileIdentifier);
        $readablePath = $file->getForLocalProcessing(false);

        $temporaryIdentifier = SshConnection::makeInstance()->transferTemporaryFile($readablePath);

        $request = [
            'storage' => $storage,
            'fileIdentifier' => $fileIdentifier,
            'localFilePath' => $temporaryIdentifier,
        ];

        $uid = $this->letterbox->sendEnvelope(new Envelope(EnvelopeDispatcher::CMD_REPLACE_FILE, $request));

        SshConnection::makeInstance()->executeRpc($uid);
        $envelope = $this->letterbox->receiveEnvelope($uid);
        return true === $envelope->getResponse();
    }

    /**
     * @param int $storage
     * @param string $fileIdentifier
     * @param string $newName
     * @return bool
     */
    public function renameForeignFile($storage, $fileIdentifier, $newName)
    {
        $expectedResult = rtrim(dirname($fileIdentifier), '/') . '/' . $newName;

        $request = [
            'storage' => $storage,
            'fileIdentifier' => $fileIdentifier,
            'newName' => $newName,
        ];

        $uid = $this->letterbox->sendEnvelope(new Envelope(EnvelopeDispatcher::CMD_RENAME_FILE, $request));

        SshConnection::makeInstance()->executeRpc($uid);
        $envelope = $this->letterbox->receiveEnvelope($uid);
        return $expectedResult === $envelope->getResponse();
    }
}
