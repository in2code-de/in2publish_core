<?php
namespace In2code\In2publishCore\Service\Environment;

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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Domain\Driver\Rpc\Envelope;
use In2code\In2publishCore\Domain\Driver\Rpc\EnvelopeDispatcher;
use In2code\In2publishCore\Domain\Driver\Rpc\Letterbox;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Used to receive static information about the foreign environment like configuration values or server variables
 */
class ForeignEnvironmentService
{
    /**
     * @var FrontendInterface
     */
    protected $cache = null;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * ForeignEnvironmentService constructor.
     */
    public function __construct()
    {
        $this->cache = $this->getCache();
        $this->logger = $this->getLogger();
    }

    /**
     * @return array
     */
    public function getDatabaseInitializationCommands()
    {
        if (!$this->cache->has('foreign_db_init')) {
            $envelope = GeneralUtility::makeInstance(Envelope::class, EnvelopeDispatcher::CMD_GET_SET_DB_INIT);
            $letterbox = GeneralUtility::makeInstance(Letterbox::class);
            $uid = $letterbox->sendEnvelope($envelope);
            $request = GeneralUtility::makeInstance(RemoteCommandRequest::class, 'rpc:execute ' . $uid);
            $response = GeneralUtility::makeInstance(RemoteCommandDispatcher::class)->dispatch($request);

            $foreignDbInit = [];

            if (!$response->isSuccessful()) {
                $this->logger->error(
                    'Could not execute RPC. Falling back to empty "setDBinit"',
                    [
                        'rpc' => $uid,
                        'errors' => $response->getErrors(),
                        'exit_status' => $response->getExitStatus(),
                        'output' => $response->getOutput(),
                    ]
                );
            } else {
                $envelope = $letterbox->receiveEnvelope($uid);
                if (false === $envelope) {
                    $this->logger->error('Could not receive envelope. Falling back to empty "setDBinit"');
                } else {
                    $dbInit = $envelope->getResponse();
                    $foreignDbInit = GeneralUtility::trimExplode(LF, str_replace('\' . LF . \'', LF, $dbInit), true);
                }
            }

            $this->cache->set('foreign_db_init', $foreignDbInit, [], 86400);
        }
        return (array)$this->cache->get('foreign_db_init');
    }

    /**
     * @return FrontendInterface
     * @codeCoverageIgnore
     */
    protected function getCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('in2publish_core');
    }

    /**
     * @return Logger
     * @codeCoverageIgnore
     */
    protected function getLogger()
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
    }
}
