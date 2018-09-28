<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteAdapter;

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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandResponse;
use In2code\In2publishCore\Communication\Shared\SshBaseAdapter;
use In2code\In2publishCore\In2publishCoreException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SshAdapter extends SshBaseAdapter implements AdapterInterface
{
    /**
     * @var resource
     */
    protected $session;

    /**
     * @param RemoteCommandRequest $request
     *
     * @return RemoteCommandResponse
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function execute(RemoteCommandRequest $request): RemoteCommandResponse
    {
        if (null === $this->session) {
            $this->logger->debug('Lazy initializing SshAdapter ssh session');
            try {
                $this->session = $this->establishSshSession();
            } catch (In2publishCoreException $e) {
                return GeneralUtility::makeInstance(
                    RemoteCommandResponse::class,
                    'An error occurred',
                    $e->getMessage(),
                    $e->getCode()
                );
            }
        }

        $command = '';

        foreach ($request->getEnvironmentVariables() as $name => $value) {
            $command .= 'export ' . escapeshellcmd($name) . '=' . escapeshellarg($value) . '; ';
        }

        $command .= 'cd ' . escapeshellarg($request->getWorkingDirectory()) . ' && ';
        $command .= escapeshellcmd($request->getPathToPhp()) . ' ';
        $command .= escapeshellcmd($request->getDispatcher()) . ' ';
        $command .= escapeshellcmd($request->getCommand());

        if ($request->hasOptions()) {
            foreach ($request->getOptions() as $option) {
                $command .= ' ' . escapeshellcmd($option);
            }
        }

        if ($request->hasArguments()) {
            foreach ($request->getArguments() as $name => $value) {
                $command .= ' ' . escapeshellcmd($name) . '=' . escapeshellarg($value);
            }
        }

        $command = rtrim($command, ';') . '; echo -en "\n"CODE_$?_CODE;';

        $this->logger->debug('Executing ssh command', ['full_command' => $command]);

        $executionResource = ssh2_exec($this->session, $command);

        $outputStream = ssh2_fetch_stream($executionResource, SSH2_STREAM_STDIO);
        $errorStream = ssh2_fetch_stream($executionResource, SSH2_STREAM_STDERR);
        stream_set_blocking($outputStream, true);
        stream_set_blocking($errorStream, true);
        $output = stream_get_contents($outputStream);
        $errors = stream_get_contents($errorStream);
        fclose($outputStream);
        fclose($errorStream);

        $output = GeneralUtility::trimExplode("\n", $output);
        $exitStatusLine = array_pop($output);

        if (1 === preg_match('~CODE_(?P<code>\d+)_CODE~', $exitStatusLine, $matches)) {
            $exitStatus = $matches['code'];
        } else {
            $this->logger->warning('Could not find exit status in command output. Using 1 as fallback');
            $exitStatus = 1;
        }

        return GeneralUtility::makeInstance(RemoteCommandResponse::class, $output, $errors, $exitStatus);
    }

    /**
     * Properly log off from the session
     */
    protected function disconnect()
    {
        if (\is_resource($this->session)) {
            ssh2_exec($this->session, 'exit');
        }
        unset($this->session);
    }
}
