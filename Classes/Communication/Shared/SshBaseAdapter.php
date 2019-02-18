<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Communication\Shared;

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

use Exception;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\In2publishCoreException;
use Psr\Log\LoggerInterface;
use Throwable;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function in_array;
use function is_resource;
use function sprintf;
use function ssh2_auth_pubkey_file;
use function ssh2_connect;
use function ssh2_fingerprint;
use function str_replace;
use function strpos;
use function strtoupper;

/**
 * Class SshBaseAdapter
 */
abstract class SshBaseAdapter
{
    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var array
     */
    protected $config = [
        'debug' => '',
        'host' => '',
        'port' => '',
        'username' => '',
        'privateKeyFileAndPathName' => '',
        'publicKeyFileAndPathName' => '',
        'privateKeyPassphrase' => '',
        'foreignKeyFingerprint' => '',
        'foreignKeyFingerprintHashingMethod' => '',
    ];

    /**
     * @var array
     */
    protected $supportedHashMethods = [
        'SSH2_FINGERPRINT_MD5',
        'SSH2_FINGERPRINT_SHA1',
    ];

    /**
     * SshBaseAdapter constructor.
     *
     * @throws Throwable
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
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

        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $this->config['debug'] = $configContainer->get('debug.showForeignKeyFingerprint');
    }

    /**
     * @return resource
     *
     * @throws In2publishCoreException
     */
    protected function establishSshSession()
    {
        $session = @ssh2_connect($this->config['host'], $this->config['port']);
        if (!is_resource($session)) {
            throw new In2publishCoreException(
                'Could not establish a SSH connection to "' . $this->config['host'] . ':' . $this->config['port'] . '"',
                1425401287
            );
        }

        $keyFingerPrint = ssh2_fingerprint($session, $this->config['foreignKeyFingerprintHashingMethod']);
        if ($keyFingerPrint !== $this->config['foreignKeyFingerprint']) {
            if (true === $this->config['debug']) {
                throw new In2publishCoreException(
                    'Identification of foreign host failed, SSH Key Fingerprint mismatch. Actual Fingerprint: "'
                    . $keyFingerPrint . '"; Configured: "' . $this->config['foreignKeyFingerprint'] . '"',
                    1426868565
                );
            } else {
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

    /**
     * Validates that all configuration values are set and contain correct values
     *
     * @throws Exception
     */
    protected function getValidatedConfig()
    {
        $config = GeneralUtility::makeInstance(ConfigContainer::class)->get('sshConnection');
        $config = $this->validateRequiredSettings($config);
        $config = $this->validateKeys($config);
        $config = $this->validateSshParameter($config);
        return $config;
    }

    /**
     * @param $config
     *
     * @return mixed
     * @throws In2publishCoreException
     */
    protected function validateRequiredSettings($config)
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

    /**
     * @param $config
     *
     * @return mixed
     * @throws In2publishCoreException
     */
    protected function validateKeys($config)
    {
        foreach (['privateKeyFileAndPathName', 'publicKeyFileAndPathName'] as $requiredFileKey) {
            if (empty($config[$requiredFileKey])) {
                throw new In2publishCoreException(
                    'SSH Connection: Option ' . $requiredFileKey . ' is empty',
                    1425400434
                );
            } elseif (!file_exists($config[$requiredFileKey])) {
                throw new In2publishCoreException(
                    'SSH Connection: The File defined in ' . $requiredFileKey . ' does not exist',
                    1425400440
                );
            } elseif (!is_readable($config[$requiredFileKey])) {
                throw new In2publishCoreException(
                    'SSH Connection: The File defined in ' . $requiredFileKey . ' is not readable',
                    1425400444
                );
            }
        }
        return $config;
    }

    /**
     * @param $config
     *
     * @return mixed
     * @throws In2publishCoreException
     */
    protected function validateSshParameter($config)
    {
        if (empty($config['foreignKeyFingerprint'])) {
            throw new In2publishCoreException('SSH Connection: Option foreignKeyFingerprint is empty', 1425400689);
        } elseif (strpos($config['foreignKeyFingerprint'], ':') !== false) {
            $config['foreignKeyFingerprint'] = strtoupper(str_replace(':', '', $config['foreignKeyFingerprint']));
        }
        if (empty($config['foreignKeyFingerprintHashingMethod'])) {
            $config['foreignKeyFingerprintHashingMethod'] = SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX;
        } else {
            if (!in_array($config['foreignKeyFingerprintHashingMethod'], $this->supportedHashMethods)) {
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

    /**
     * Destroy all sessions and connections
     *
     * @return void
     */
    abstract protected function disconnect();

    /**
     *
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
