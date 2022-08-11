<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Shared;

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

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\In2publishCoreException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

use function constant;
use function escapeshellarg;
use function escapeshellcmd;
use function file_exists;
use function in_array;
use function is_readable;
use function is_resource;
use function sprintf;
use function ssh2_auth_pubkey_file;
use function ssh2_connect;
use function ssh2_fingerprint;
use function str_replace;
use function strpos;
use function strtoupper;

abstract class SshBaseAdapter implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ConfigContainerInjection;

    protected array $config = [
        'debug' => '',
        'host' => '',
        'port' => '',
        'username' => '',
        'privateKeyFileAndPathName' => '',
        'publicKeyFileAndPathName' => '',
        'privateKeyPassphrase' => '',
        'enableForeignKeyFingerprintCheck' => '',
        'foreignKeyFingerprint' => '',
        'foreignKeyFingerprintHashingMethod' => '',
    ];
    protected array $supportedHashMethods = [
        'SSH2_FINGERPRINT_MD5',
        'SSH2_FINGERPRINT_SHA1',
    ];
    protected bool $initialized = false;

    public function init(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->logger->debug('Initializing SshAdapter configuration');

        try {
            $this->config = $this->getValidatedConfig();
        } catch (Throwable $exception) {
            $this->logger->error(
                'Invalid SSH connection configuration detected',
                ['message' => $exception->getMessage()]
            );
            throw $exception;
        }

        $this->config['debug'] = $this->configContainer->get('debug.showForeignKeyFingerprint');

        $this->initialized = true;
    }

    /**
     * @return resource
     *
     * @throws In2publishCoreException
     *
     * @SuppressWarnings(PHPMD.ErrorControlOperator) Don't leak sensitive error info.
     */
    protected function establishSshSession()
    {
        $this->init();
        $session = @ssh2_connect($this->config['host'], $this->config['port']);
        if (!is_resource($session)) {
            throw new In2publishCoreException(
                'Could not establish a SSH connection to "' . $this->config['host'] . ':' . $this->config['port'] . '"',
                1425401287
            );
        }

        if ($this->config['enableForeignKeyFingerprintCheck']) {
            $keyFingerPrint = ssh2_fingerprint($session, $this->config['foreignKeyFingerprintHashingMethod']);
            if ($keyFingerPrint !== $this->config['foreignKeyFingerprint']) {
                if (true === $this->config['debug']) {
                    throw new In2publishCoreException(
                        'Identification of foreign host failed, SSH Key Fingerprint mismatch. Actual Fingerprint: "'
                        . $keyFingerPrint . '"; Configured: "' . $this->config['foreignKeyFingerprint'] . '"',
                        1426868565
                    );
                }
                throw new In2publishCoreException(
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
            throw new In2publishCoreException(
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

    protected function prepareCommand(RemoteCommandRequest $request): string
    {
        $this->init();
        $command = '';

        foreach ($request->getEnvironmentVariables() as $name => $value) {
            $command .= 'export ' . escapeshellcmd((string)$name) . '=' . escapeshellarg((string)$value) . '; ';
        }

        $command .= 'cd ' . escapeshellarg($request->getWorkingDirectory()) . ' && ';
        $command .= escapeshellcmd($request->getPathToPhp()) . ' ';
        $command .= escapeshellcmd($request->getDispatcher()) . ' ';
        $command .= escapeshellcmd($request->getCommand());

        if ($request->hasOptions()) {
            foreach ($request->getOptions() as $option) {
                $command .= ' ' . escapeshellcmd((string)$option);
            }
        }

        if ($request->hasArguments()) {
            foreach ($request->getArguments() as $name => $value) {
                $command .= ' ' . escapeshellcmd((string)$name) . '=' . escapeshellarg((string)$value);
            }
        }
        return $command;
    }

    /**
     * Validates that all configuration values are set and contain correct values
     *
     * @throws In2publishCoreException
     */
    protected function getValidatedConfig(): array
    {
        $config = $this->configContainer->get('sshConnection');
        $config = $this->validateRequiredSettings($config);
        $config = $this->validateKeys($config);
        return $this->validateSshParameter($config);
    }

    /** @throws In2publishCoreException */
    protected function validateRequiredSettings(array $config): array
    {
        if (empty($config)) {
            throw new In2publishCoreException('SSH Connection: Missing configuration', 1428492639);
        }
        if (empty($config['host'])) {
            throw new In2publishCoreException('SSH Connection: Option host is empty', 1425400317);
        }
        if (empty($config['port'])) {
            throw new In2publishCoreException('SSH Connection: Option port is empty', 1493823206);
        }
        if (empty($config['username'])) {
            throw new In2publishCoreException('SSH Connection: Option username is empty', 1425400379);
        }
        return $config;
    }

    /** @throws In2publishCoreException */
    protected function validateKeys(array $config): array
    {
        foreach (['privateKeyFileAndPathName', 'publicKeyFileAndPathName'] as $requiredFileKey) {
            if (empty($config[$requiredFileKey])) {
                throw new In2publishCoreException(
                    'SSH Connection: Option ' . $requiredFileKey . ' is empty',
                    1425400434
                );
            }
            if (!file_exists($config[$requiredFileKey])) {
                throw new In2publishCoreException(
                    'SSH Connection: The File defined in ' . $requiredFileKey . ' does not exist',
                    1425400440
                );
            }
            if (!is_readable($config[$requiredFileKey])) {
                throw new In2publishCoreException(
                    'SSH Connection: The File defined in ' . $requiredFileKey . ' is not readable',
                    1425400444
                );
            }
        }
        return $config;
    }

    /** @throws In2publishCoreException */
    protected function validateSshParameter(array $config): array
    {
        if (empty($config['foreignKeyFingerprint'])) {
            throw new In2publishCoreException('SSH Connection: Option foreignKeyFingerprint is empty', 1425400689);
        }
        if (strpos($config['foreignKeyFingerprint'], ':') !== false) {
            $config['foreignKeyFingerprint'] = strtoupper(str_replace(':', '', $config['foreignKeyFingerprint']));
        }
        if (empty($config['foreignKeyFingerprintHashingMethod'])) {
            $config['foreignKeyFingerprintHashingMethod'] = SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX;
        } else {
            if (!in_array($config['foreignKeyFingerprintHashingMethod'], $this->supportedHashMethods, true)) {
                throw new In2publishCoreException(
                    'SSH Connection: The defined foreignKeyFingerprintHashingMethod is not supported',
                    1493822754
                );
            }
            $config['foreignKeyFingerprintHashingMethod'] = constant($config['foreignKeyFingerprintHashingMethod'])
                | SSH2_FINGERPRINT_HEX;
        }
        return $config;
    }

    /** Destroy all sessions and connections */
    abstract protected function disconnect(): void;

    public function __destruct()
    {
        $this->disconnect();
    }
}
