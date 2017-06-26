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

use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
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
        $this->contextService = GeneralUtility::makeInstance(
            ContextService::class
        );
        $this->keepEnvelopes = (bool)ConfigurationUtility::getConfiguration('debug.keepEnvelopes');
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

        if (0 === $uid || 0 === $database->exec_SELECTcountRows('uid', static::TABLE, 'uid=' . $uid)) {
            if (true === $database->exec_INSERTquery(static::TABLE, $envelope->toArray())) {
                if ($uid > 0) {
                    return $uid;
                }
                $uid = $database->sql_insert_id();
                $envelope->setUid($uid);
                return $uid;
            } else {
                $this->logger->error(
                    'Failed to send envelope [' . $uid . ']',
                    [
                        'envelope' => $envelope->toArray(),
                        'error' => $database->sql_error(),
                        'errno' => $database->sql_errno(),
                    ]
                );
            }
        } else {
            if (false === $database->exec_UPDATEquery(static::TABLE, 'uid=' . $uid, $envelope->toArray())) {
                $this->logger->error(
                    'Failed to update envelope [' . $uid . ']',
                    [
                        'envelope' => $envelope->toArray(),
                        'error' => $database->sql_error(),
                        'errno' => $database->sql_errno(),
                    ]
                );
            } else {
                return true;
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

        $envelopeData = $database->exec_SELECTgetSingleRow(
            'command,request,response,uid',
            static::TABLE,
            'uid=' . $uid
        );

        if (is_array($envelopeData)) {
            $envelope = Envelope::fromArray($envelopeData);
            if (!$this->keepEnvelopes && $burnEnvelope) {
                $database->exec_DELETEquery(static::TABLE, 'uid=' . $uid);
            }
        } else {
            $this->logger->error(
                'Failed to receive envelope [' . $uid . '] "' . $database->sql_error() . '"',
                [
                    'error' => $database->sql_error(),
                    'errno' => $database->sql_errno(),
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

        if ($database instanceof DatabaseConnection && $database->isConnected()) {
            return $database->exec_SELECTcountRows('uid', static::TABLE, 'response IS NOT NULL');
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
        $database->exec_DELETEquery(static::TABLE, 'response IS NOT NULL');
    }
}
