<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter;

/*
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Communication\Shared\SshBaseAdapter;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Environment\ForeignEnvironmentService;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function decoct;
use function dirname;
use function filesize;
use function fopen;
use function function_exists;
use function is_dir;
use function is_resource;
use function octdec;
use function ssh2_exec;
use function ssh2_sftp;
use function ssh2_sftp_chmod;
use function ssh2_sftp_mkdir;
use function stream_copy_to_stream;

class SshAdapter extends SshBaseAdapter implements AdapterInterface
{
    public const ADAPTER_KEY = 'ssh';

    /**
     * @var null|resource
     */
    protected $sshSession;

    /**
     * @var null|resource
     */
    protected $sftSession;

    /**
     * @var array
     */
    protected $createMasks = [
        'decimalFileMask' => 0000,
        'decimalFolderMask' => 0000,
    ];

    /**
     * @param string $source
     * @param string $target
     *
     * @return bool
     * @throws In2publishCoreException
     */
    public function copyFileToRemote(string $source, string $target): bool
    {
        if (null === $this->sshSession) {
            $this->sshSession = $this->establishSshSession();
            $this->sftSession = ssh2_sftp($this->sshSession);

            $this->logger->debug('Setting create masks');
            $createMasks = GeneralUtility::makeInstance(ForeignEnvironmentService::class)->getCreateMasks();
            $this->createMasks['decimalFileMask'] = octdec($createMasks['file']);
            $this->createMasks['decimalFolderMask'] = octdec($createMasks['folder']);
        }

        $this->ensureTargetFolderExists(dirname($target));

        $sourceStream = fopen($source, 'r');

        if (!is_resource($sourceStream)) {
            $this->logger->error('Could not open local file for reading', ['source' => $source]);
            throw new In2publishCoreException('Could not open stream on local file "' . $source . '"', 1425466802);
        }

        try {
            $targetStream = fopen('ssh2.sftp://' . ((int)$this->sftSession) . $target, 'w');
        } catch (Throwable $exception) {
            $this->logger->critical('Caught exception while trying to open sftp stream', ['exception' => $exception]);
            throw new In2publishCoreException('Could not open stream on foreign: "' . $exception . '"', 1425467980);
        }

        if (!is_resource($targetStream)) {
            $this->logger->error('Could not open foreign file for reading', ['source' => $source]);
            throw new In2publishCoreException('Could not open stream on foreign file "' . $target . '"', 1425466826);
        }

        $bytesToWrite = filesize($source);
        $bytesWritten = stream_copy_to_stream($sourceStream, $targetStream);

        if ($bytesToWrite !== $bytesWritten) {
            $this->logger->error(
                'Writing remote file did not write all bytes',
                ['bytesToWrite' => $bytesToWrite, 'bytesWritten' => $bytesWritten]
            );
            throw new In2publishCoreException('Writing remote file"' . $target . '" was not completed', 1425467808);
        }

        $success = $this->setRemoteFilePermissions($target);

        if (true !== $success) {
            if (true === $this->config['ignoreChmodFail']) {
                $this->logger->error('Could not set file permissions but continue because ignoreChmodFail is set.');
            } else {
                $this->logger->error('Could not set file permissions. Throwing exception.');
                throw new In2publishCoreException('Failed to set permissions for "' . $target . '"', 1440748670);
            }
        } else {
            return true;
        }

        return false;
    }

    /**
     * @param string $target
     *
     * @return bool
     */
    protected function setRemoteFilePermissions(string $target): bool
    {
        // ssh2_sftp_chmod since PECL ssh2 >= 0.12 but has bugs in PHP 7
        if (PHP_MAJOR_VERSION < 7 && function_exists('ssh2_sftp_chmod')) {
            if (ssh2_sftp_chmod($this->sftSession, $target, $this->createMasks['decimalFolderMask'])) {
                return true;
            }

            $this->logger->error('Failed to set response via ssh2_sftp_chmod');
        } else {
            $request = GeneralUtility::makeInstance(
                RemoteCommandRequest::class,
                'chmod',
                [],
                [decoct($this->createMasks['decimalFileMask']), $target]
            );
            $request->usePhp(false);
            $request->setDispatcher('');
            $request->setEnvironmentVariables([]);

            $response = GeneralUtility::makeInstance(RemoteCommandDispatcher::class)->dispatch($request);

            if ($response->isSuccessful()) {
                return true;
            }

            $this->logger->error(
                'Failed to set response via RCE API',
                [
                    'output' => $response->getOutput(),
                    'errors' => $response->getErrors(),
                    'exit_status' => $response->getExitStatus(),
                    'file_mask_octal' => decoct($this->createMasks['decimalFileMask']),
                    'target' => $target,
                ]
            );
        }

        return false;
    }

    /**
     * @param string $folder
     *
     * @return bool
     */
    protected function ensureTargetFolderExists(string $folder): bool
    {
        if (!$this->remoteFolderExists($folder)) {
            return $this->createRemoteFolder($folder);
        }
        return true;
    }

    /**
     * @param string $folder
     *
     * @return bool
     */
    protected function remoteFolderExists(string $folder): bool
    {
        return is_dir('ssh2.sftp://' . ((int)$this->sftSession) . $folder);
    }

    /**
     * @param string $folder
     *
     * @return bool
     */
    protected function createRemoteFolder(string $folder): bool
    {
        return ssh2_sftp_mkdir($this->sftSession, $folder, $this->createMasks['decimalFolderMask'], true);
    }

    /**
     *
     */
    protected function disconnect()
    {
        if (is_resource($this->sshSession)) {
            ssh2_exec($this->sshSession, 'exit');
        }
        unset(
            $this->sshSession,
            $this->sftSession
        );
    }
}
