<?php
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
use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SshAdapter
 */
class SshAdapter implements AdapterInterface
{
    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var bool
     */
    protected $chmodEnabled = false;

    /**
     * @var array
     */
    protected $config = array(
        'chmodEnabled' => '',
        'debug' => '',
        'host' => '',
        'port' => '',
        'username' => '',
        'privateKeyFileAndPathName' => '',
        'publicKeyFileAndPathName' => '',
        'privateKeyPassphrase' => '',
        'foreignKeyFingerprint' => '',
        'foreignKeyFingerprintHashingMethod' => '',
    );

    /**
     * @var array
     */
    protected $supportedHashMethods = array(
        'SSH2_FINGERPRINT_MD5',
        'SSH2_FINGERPRINT_SHA1',
    );

    /**
     * @var resource
     */
    protected $session = null;

    /**
     * SshAdapter constructor.
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->logger->debug('Initializing SshAdapter configuration');

        $this->config = $this->getValidatedConfig();
        $this->config['chmodEnabled'] = function_exists('ssh2_sftp_chmod');
        $this->config['debug'] = (bool)ConfigurationUtility::getConfiguration('debug.showForeignKeyFingerprint');
    }

    /**
     * @param RemoteCommandRequest $request
     * @return RemoteCommandResponse
     */
    public function execute(RemoteCommandRequest $request)
    {
        if (null === $this->session) {
            $this->logger->debug('Lazy initializing SshAdapter ssh session');
            $this->session = $this->establishSession();
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
        if (is_resource($this->session)) {
            ssh2_exec($this->session, 'exit');
        }
        unset($this->session);
    }

    /**
     * @return resource
     * @throws \Exception
     */
    protected function establishSession()
    {
        $session = @ssh2_connect($this->config['host'], $this->config['port']);
        if (!is_resource($session)) {
            throw new \Exception(
                'Could not establish a SSH connection to "' . $this->config['host'] . ':' . $this->config['port'] . '"',
                1425401287
            );
        }

        $keyFingerPrint = ssh2_fingerprint($session, $this->config['foreignKeyFingerprintHashingMethod']);
        if ($keyFingerPrint !== $this->config['foreignKeyFingerprint']) {
            if (true === $this->config['debug']) {
                throw new \Exception(
                    'Identification of foreign host failed, SSH Key Fingerprint mismatch. Actual Fingerprint: "'
                    . $keyFingerPrint . '"; Configured: "' . $this->config['foreignKeyFingerprint'] . '"',
                    1426868565
                );
            } else {
                throw new \Exception(
                    'Identification of foreign host failed, SSH Key Fingerprint mismatch!!!',
                    1425401452
                );
            }
        }

        $authorizationSuccess = @ssh2_auth_pubkey_file(
            $session,
            $this->config['username'],
            $this->config['publicKeyFileAndPathName'],
            $this->config['privateKeyFileAndPathName'],
            $this->config['privateKeyPassphrase']
        );
        if (true !== $authorizationSuccess) {
            throw new \Exception(
                sprintf(
                    'Could not authenticate the SSH connection for "%s@%s:%d" with the given SSH key pair',
                    $this->config['username'],
                    $this->config['host'],
                    $this->config['port']
                ),
                1425401293
            );
        }

        return $session;
    }

    /**
     * Validates that all configuration values are set and contain correct values
     *
     * @throws \Exception
     */
    protected function getValidatedConfig()
    {
        $config = ConfigurationUtility::getConfiguration('sshConnection');
        if (empty($config)) {
            throw new \Exception('SSH Connection: Missing configuration', 1428492639);
        }
        if (empty($config['host'])) {
            throw new \Exception('SSH Connection: Option host is empty', 1425400317);
        }
        if (empty($config['port'])) {
            throw new \Exception('SSH Connection: Option port is empty', 1493823206);
        }
        if (empty($config['username'])) {
            throw new \Exception('SSH Connection: Option username is empty', 1425400379);
        }
        foreach (array('privateKeyFileAndPathName', 'publicKeyFileAndPathName') as $requiredFileKey) {
            if (empty($config[$requiredFileKey])) {
                throw new \Exception('SSH Connection: Option ' . $requiredFileKey . ' is empty', 1425400434);
            } elseif (!file_exists($config[$requiredFileKey])) {
                throw new \Exception(
                    'SSH Connection: The File defined in ' . $requiredFileKey . ' does not exist',
                    1425400440
                );
            } elseif (!is_readable($config[$requiredFileKey])) {
                throw new \Exception(
                    'SSH Connection: The File defined in ' . $requiredFileKey . ' is not readable',
                    1425400444
                );
            }
        }
        if (empty($config['privateKeyPassphrase'])) {
            $config['privateKeyPassphrase'] = null;
        }
        if (empty($config['foreignKeyFingerprint'])) {
            throw new \Exception('SSH Connection: Option foreignKeyFingerprint is empty', 1425400689);
        } elseif (strpos($config['foreignKeyFingerprint'], ':') !== false) {
            $config['foreignKeyFingerprint'] = strtoupper(str_replace(':', '', $config['foreignKeyFingerprint']));
        }
        if (empty($config['foreignKeyFingerprintHashingMethod'])) {
            $config['foreignKeyFingerprintHashingMethod'] = SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX;
        } else {
            if (!in_array($config['foreignKeyFingerprintHashingMethod'], $this->supportedHashMethods)) {
                throw new \Exception(
                    'SSH Connection: The defined foreignKeyFingerprintHashingMethod is not supported',
                    1493822754
                );
            }
            $config['foreignKeyFingerprintHashingMethod'] = constant($config['foreignKeyFingerprintHashingMethod'])
                                                            | SSH2_FINGERPRINT_HEX;
        }
        return $config;
    }

    /**
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
