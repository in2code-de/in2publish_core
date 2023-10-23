<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TemporaryAssetTransmission;

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

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\Exception\FileMissingException;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\SingletonInterface;

use function file_exists;
use function hash;
use function rtrim;

class AssetTransmitter implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected string $foreignVarPath;
    protected AdapterInterface $adapter;
    protected int $fileTransmissionTimeout = 0;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->foreignVarPath = rtrim($configContainer->get('foreign.varPath'), '/');
        $this->fileTransmissionTimeout = (int)$configContainer->get('adapter.local.fileTransmissionTimeout');
    }

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectAdapter(AdapterInterface $adapter): void
    {
        $this->adapter = $adapter;
    }

    /**
     * @param string $source Absolute local path to file => return value of
     *     \TYPO3\CMS\Core\Resource\Driver\DriverInterface::getFileForLocalProcessing
     *
     * @return string The absolute file name on the remote server where the file was stored.
     *
     * @throws FileMissingException
     */
    public function transmitTemporaryFile(string $source): string
    {
        $this->logger->info('Transmission of file requested', ['source' => $source]);

        if (!file_exists($source)) {
            $this->logger->error('File does not exist', ['source' => $source]);
            throw new FileMissingException($source);
        }

        $identifierHash = hash('sha1', $source);
        $target = $this->foreignVarPath . '/transient/' . $identifierHash;

        $success = $this->adapter->copyFileToRemote($source, $target);

        // On slow file systems PublisherTests may fail with a TATAPI error. A timeout may resolve this problem.
        // see: https://projekte.in2code.de/issues/48012
        if ($this->fileTransmissionTimeout > 0) {
            sleep($this->fileTransmissionTimeout);
        }

        if (true === $success) {
            $this->logger->debug(
                'Successfully transferred file to foreign',
                ['target' => $target, 'identifierHash' => $identifierHash],
            );
        } else {
            $this->logger->error(
                'Failed to transfer file to foreign',
                ['target' => $target, 'identifierHash' => $identifierHash],
            );
        }

        return $target;
    }
}
