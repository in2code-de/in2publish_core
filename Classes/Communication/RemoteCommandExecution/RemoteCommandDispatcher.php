<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Communication\RemoteCommandExecution;

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

use In2code\In2publishCore\Communication\AdapterRegistry;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter\AdapterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function microtime;

class RemoteCommandDispatcher implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected ?AdapterInterface $adapter = null;

    protected AdapterRegistry $adapterRegistry;

    public function __construct(AdapterRegistry $adapterRegistry)
    {
        $this->adapterRegistry = $adapterRegistry;
    }

    /**
     * @param RemoteCommandRequest $request
     *
     * @return RemoteCommandResponse
     */
    public function dispatch(RemoteCommandRequest $request): RemoteCommandResponse
    {
        if (null === $this->adapter) {
            try {
                $adapterClass = $this->adapterRegistry->getAdapter(AdapterInterface::class);
                $this->adapter = GeneralUtility::makeInstance($adapterClass);
            } catch (Throwable $exception) {
                $this->logger->debug('SshAdapter initialization failed. See previous log for reason.');
                return new RemoteCommandResponse([], [$exception->getMessage()], 1);
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
            ['output' => $response->getOutputString(), 'errors' => $response->getErrorsString()]
        );
        return $response;
    }
}
