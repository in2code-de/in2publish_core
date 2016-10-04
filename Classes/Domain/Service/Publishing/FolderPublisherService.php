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
use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Security\SshConnection;
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
     * @param Record $record
     * @return bool
     */
    public function publish(Record $record)
    {
        switch ($record->getState()) {
            case RecordInterface::RECORD_STATE_CHANGED:
                throw new \LogicException(
                    'The given folder is flagged as changed, but this case is currently not implemented',
                    1475584313
                );
                break;
            case RecordInterface::RECORD_STATE_UNCHANGED:
                throw new \LogicException(
                    'The given folder is unchanged but you should not be able to force the publishing',
                    1475583117
                );
                break;
            case RecordInterface::RECORD_STATE_ADDED:
                $identifier = $record->getLocalProperty('identifier');
                $parentIdentifier = dirname($identifier);
                if (empty($parentIdentifier)) {
                    $parentIdentifier = '/';
                }
                $envelope = new Envelope(
                    EnvelopeDispatcher::CMD_CREATE_FOLDER,
                    array(
                        'storage' => $record->getLocalProperty('storage'),
                        'newFolderName' => basename($identifier),
                        'parentFolderIdentifier' => $parentIdentifier,
                        'recursive' => true,
                    )
                );
                break;
            case RecordInterface::RECORD_STATE_DELETED:
                break;
            case RecordInterface::RECORD_STATE_MOVED:
                throw new \LogicException(
                    'The given folder is flagged as moved, but this case is currently not implemented',
                    1475583059
                );
                break;
            default:
                throw new \LogicException(
                    'The given folder record has no valid state',
                    1475583031
                );
        }
        $uid = $this->letterbox->sendEnvelope($envelope);
        SshConnection::makeInstance()->executeRpc($uid);
        $responseEnvelope = $this->letterbox->receiveEnvelope($uid);
        $response = $responseEnvelope->getResponse();
        if ($response === $identifier) {
            $record->setState(RecordInterface::RECORD_STATE_UNCHANGED);
            return true;
        }
        return false;
    }
}
