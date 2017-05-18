<?php
namespace In2code\In2publishCore\Communication\RemoteCommandExecution;

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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\AdapterInterface;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\SshAdapter;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RemoteCommandDispatcher
 */
class RemoteCommandDispatcher implements SingletonInterface
{
    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var AdapterInterface
     */
    protected $adapter = null;

    /**
     * RemoteCommandDispatcher constructor.
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->logger->debug('Initializing RemoteCommandDispatcher');
    }

    /**
     * @param RemoteCommandRequest $request
     * @return RemoteCommandResponse
     */
    public function dispatch(RemoteCommandRequest $request)
    {
        if (null === $this->adapter) {
            $this->logger->debug('Lazy initializing SshAdapter');
            try {
                $this->adapter = GeneralUtility::makeInstance(SshAdapter::class);
            } catch (\Exception $exception) {
                $this->logger->debug('SshAdapter initialization failed. See previous log for reason.');
                return GeneralUtility::makeInstance(RemoteCommandResponse::class, [], [$exception->getMessage()], 1);
            }
        }

        $this->logger->debug('Dispatching command request', ['command' => $request->getCommand()]);
        $start = microtime(true);

        $response = $this->adapter->execute($request);

        $this->logger->info(
            'Dispatched command request',
            ['exec_time' => microtime(true) - $start, 'exit_status' => $response->getExitStatus()]
        );
        $this->logger->debug(
            'Command dispatching results',
            ['output' => $response->getOutput(), 'errors' => $response->getErrors()]
        );
        return $response;
    }
}
