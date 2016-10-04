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
 * Class FolderPublisherService
 */
class FolderPublisherService
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
     * @param string $identifier
     * @return bool
     */
    public function publish($identifier)
    {
        list($storage, $folderIdentifier) = GeneralUtility::trimExplode(':', $identifier);

        $fileExists = ResourceFactory::getInstance()->getStorageObject($storage)->hasFolder($folderIdentifier);
        // determine if the folder should get published or deleted.
        // if it exists locally then create it on foreign, else delete it.
        if ($fileExists) {
            $envelope = new Envelope(
                EnvelopeDispatcher::CMD_CREATE_FOLDER,
                array(
                    'storage' => $storage,
                    'newFolderName' => basename($folderIdentifier),
                    'parentFolderIdentifier' => dirname($folderIdentifier),
                    'recursive' => true,
                )
            );
        } else {
            $envelope = new Envelope(
                EnvelopeDispatcher::CMD_DELETE_FOLDER,
                array(
                    'storage' => $storage,
                    'folderIdentifier' => $folderIdentifier,
                    'deleteRecursively' => true,
                )
            );
        }

        $uid = $this->letterbox->sendEnvelope($envelope);
        SshConnection::makeInstance()->executeRpc($uid);
        $responseEnvelope = $this->letterbox->receiveEnvelope($uid);
        $response = $responseEnvelope->getResponse();
        return $fileExists ? $response === $folderIdentifier : $response;
    }
}
