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

use In2code\In2publishCore\CommonInjection\DatabaseOfForeignInjection;
use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\SingletonInterface;

use function is_string;
use function serialize;
use function str_split;
use function unserialize;

class Letterbox implements LoggerAwareInterface, SingletonInterface
{
    use LoggerAwareTrait;
    use DatabaseOfForeignInjection;

    protected const CHUNK_SIZE = 65000;
    protected bool $keepEnvelopes;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function __construct(\In2code\In2publishCore\Component\ConfigContainer\ConfigContainer $keepEnvelopes)
    {
        // Type cast this value because this class is also used on foreign and there's no such setting.
        $this->keepEnvelopes = (bool)$configContainer->get('debug.keepEnvelopes');
    }

    /**
     * @return bool|int false for errors, int for successful sent envelopes and true for updated envelopes (yes, ugly)
     * @throws Throwable
     */
    public function sendEnvelope(Envelope $envelope)
    {
        $uid = $envelope->getUid();

        if (0 === $uid || 0 === $this->databaseOfForeign->count('uid', 'tx_in2code_rpc_request', ['uid' => $uid])) {
            try {
                return $this->insertNewEnvelope($envelope);
            } catch (Throwable $exception) {
                $this->logger->error(
                    'Failed to send envelope [' . $uid . ']',
                    ['envelope' => $envelope->toArray(), 'exception' => $exception],
                );
                throw $exception;
            }
        }

        try {
            $this->updateEnvelope($envelope, $uid);
            return true;
        } catch (Throwable $exception) {
            $this->logger->error(
                'Failed to update envelope [' . $uid . ']',
                ['envelope' => $envelope->toArray(), 'exception' => $exception],
            );
        }
        return false;
    }

    /**
     * @param int $uid
     * @param bool $burnEnvelope a.k.a. burn after reading, overridden by global debug setting
     *
     * @return bool|Envelope
     * @throws Throwable
     *
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) Don't be so picky :/
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function receiveEnvelope(int $uid, bool $burnEnvelope = true)
    {
        $query = $this->databaseOfForeign->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('req.uid', 'req.command', 'data.payload', 'data.data_type', 'data.sorting')
              ->from('tx_in2code_rpc_request', 'req')
              ->join('req', 'tx_in2code_rpc_data', 'data', 'req.uid = data.request')
              ->where($query->expr()->eq('uid', $uid))
              ->orderBy('data.sorting');
        try {
            $result = $query->executeQuery();
            $rows = $result->fetchAllAssociative();
        } catch (Throwable $exception) {
            $this->logger->error(
                'Failed to receive envelope [' . $uid . '] "' . $exception . '"',
                ['exception' => $exception],
            );
            return false;
        }

        if (empty($rows[0])) {
            $this->logger->error(
                'No Envelope UID is given. Please check your database settings.'
            );
            return false;
        }

        $uid = $rows[0]['uid'];
        $command = $rows[0]['command'];

        $request = '';
        $response = null;
        foreach ($rows as $row) {
            if ('request' === $row['data_type']) {
                $request .= $row['payload'];
            }
            if ('response' === $row['data_type']) {
                if (null === $response) {
                    $response = '';
                }
                $response .= $row['payload'];
            }
        }

        if (is_string($response)) {
            $response = unserialize($response);
        }
        $envelope = new Envelope($command, unserialize($request), $response, $uid);

        if (!$this->keepEnvelopes && $burnEnvelope) {
            $this->databaseOfForeign->delete('tx_in2code_rpc_request', ['uid' => $uid]);
            $this->databaseOfForeign->delete('tx_in2code_rpc_data', ['request' => $uid]);
        }
        return $envelope;
    }

    public function hasUnAnsweredEnvelopes(): bool
    {
        $query = $this->databaseOfForeign->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->count('uid')
              ->from('tx_in2code_rpc_request', 'req')
              ->join('req', 'tx_in2code_rpc_data', 'data', 'req.uid = data.request')
              ->where($query->expr()->eq('data.data_type', $query->createNamedParameter('response')));
        return $query->executeQuery()->fetchOne() > 0;
    }

    public function removeAnsweredEnvelopes(): void
    {
        $query = $this->databaseOfForeign->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $query->select('uid')
              ->from('tx_in2code_rpc_request', 'req')
              ->join('req', 'tx_in2code_rpc_data', 'data', 'req.uid = data.request')
              ->where($query->expr()->eq('data.data_type', $query->createNamedParameter('response')));
        $uid = $query->executeQuery()->fetchOne();
        $this->databaseOfForeign->delete('tx_in2code_rpc_request', ['uid' => $uid]);
        $this->databaseOfForeign->delete('tx_in2code_rpc_data', ['request' => $uid]);
    }

    protected function insertNewEnvelope(Envelope $envelope): int
    {
        $this->databaseOfForeign->beginTransaction();

        $this->databaseOfForeign->insert('tx_in2code_rpc_request', [
            'command' => $envelope->getCommand(),
        ]);
        $uid = (int)$this->databaseOfForeign->lastInsertId();

        $request = serialize($envelope->getRequest());
        $chunks = str_split($request, self::CHUNK_SIZE);
        foreach ($chunks as $sorting => $chunk) {
            $this->databaseOfForeign->insert('tx_in2code_rpc_data', [
                'request' => $uid,
                'data_type' => 'request',
                'payload' => $chunk,
                'sorting' => $sorting,
            ]);
        }

        $this->databaseOfForeign->commit();
        return $uid;
    }

    protected function updateEnvelope(Envelope $envelope, int $uid): void
    {
        $this->databaseOfForeign->beginTransaction();

        $response = serialize($envelope->getResponse());
        $chunks = str_split($response, self::CHUNK_SIZE);
        foreach ($chunks as $sorting => $chunk) {
            $this->databaseOfForeign->insert('tx_in2code_rpc_data', [
                'request' => $uid,
                'data_type' => 'response',
                'payload' => $chunk,
                'sorting' => $sorting,
            ]);
        }
        $this->databaseOfForeign->commit();
    }
}
