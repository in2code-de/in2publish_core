<?php
namespace In2code\In2publishCore\Communication\RemoteProcedureCall;

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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Letterbox
 */
class Letterbox
{
    const TABLE = 'tx_in2code_in2publish_envelope';

    /**
     * @var ContextService
     */
    protected $contextService = null;

    /**
     * @var bool
     */
    protected $keepEnvelopes = true;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * Letterbox constructor.
     */
    public function __construct()
    {
        $this->contextService = GeneralUtility::makeInstance(ContextService::class);
        $this->keepEnvelopes = GeneralUtility::makeInstance(ConfigContainer::class)->get('debug.keepEnvelopes');
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
    }

    /**
     * @param Envelope $envelope
     * @return bool|int false for errors, int for successful sent envelopes and true for updated envelopes (yes, ugly)
     */
    public function sendEnvelope(Envelope $envelope)
    {
        if ($this->contextService->isLocal()) {
            $database = DatabaseUtility::buildForeignDatabaseConnection();
        } else {
            $database = DatabaseUtility::buildLocalDatabaseConnection();
        }

        $uid = (int)$envelope->getUid();

        if (0 === $uid || 0 === $database->count('uid', static::TABLE, ['uid' => $uid])) {
            if (1 === $database->insert(static::TABLE, $envelope->toArray())) {
                if ($uid <= 0) {
                    $uid = $database->lastInsertId();
                    $envelope->setUid($uid);
                }
                return $uid;
            } else {
                $this->logger->error(
                    'Failed to send envelope [' . $uid . ']',
                    [
                        'envelope' => $envelope->toArray(),
                        'error' => $database->errorInfo(),
                        'errno' => $database->errorCode(),
                    ]
                );
            }
        } else {
            if (1 === $database->update(static::TABLE, $envelope->toArray(), ['uid' => $uid])) {
                return true;
            } else {
                $this->logger->error(
                    'Failed to update envelope [' . $uid . ']',
                    [
                        'envelope' => $envelope->toArray(),
                        'error' => $database->errorInfo(),
                        'errno' => $database->errorCode(),
                    ]
                );
            }
        }
        return false;
    }

    /**
     * @param int $uid
     * @param bool $burnEnvelope a.k.a. burn after reading, overridden by global debug setting
     * @return bool|Envelope
     */
    public function receiveEnvelope($uid, $burnEnvelope = true)
    {
        $uid = (int)$uid;

        if ($this->contextService->isForeign()) {
            $database = DatabaseUtility::buildLocalDatabaseConnection();
        } else {
            $database = DatabaseUtility::buildForeignDatabaseConnection();
        }

        $envelopeData = $database
            ->select(
                ['command', 'request', 'response', 'uid'],
                static::TABLE,
                ['uid' => $uid]
            )
            ->fetch();

        if (is_array($envelopeData)) {
            $envelope = Envelope::fromArray($envelopeData);
            if (!$this->keepEnvelopes && $burnEnvelope) {
                $database->delete(static::TABLE, ['uid' => $uid]);
            }
        } else {
            $this->logger->error(
                'Failed to receive envelope [' . $uid . '] "' . $database->errorInfo() . '"',
                [
                    'error' => $database->errorInfo(),
                    'errno' => $database->errorCode(),
                ]
            );
            $envelope = false;
        }
        return $envelope;
    }

    /**
     * @return false|int
     */
    public function hasUnAnsweredEnvelopes()
    {
        if ($this->contextService->isLocal()) {
            $database = DatabaseUtility::buildForeignDatabaseConnection();
        } else {
            $database = DatabaseUtility::buildLocalDatabaseConnection();
        }

        if ($database instanceof Connection && $database->isConnected()) {
            $query = $database->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $query->count('uid')->from(static::TABLE)->where($query->expr()->isNotNull('response'));
            return $query->execute()->fetch() > 0;
        }
        return false;
    }

    /**
     *
     */
    public function removeAnsweredEnvelopes()
    {
        if ($this->contextService->isLocal()) {
            $database = DatabaseUtility::buildForeignDatabaseConnection();
        } else {
            $database = DatabaseUtility::buildLocalDatabaseConnection();
        }
        $query = $database->createQueryBuilder();
        $query->delete(static::TABLE)->where($query->expr()->isNotNull('response'))->execute();
    }
}
