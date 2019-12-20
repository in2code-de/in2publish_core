<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Communication\TemporaryAssetTransmission;

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

use In2code\In2publishCore\Communication\AdapterRegistry;
use In2code\In2publishCore\Communication\TemporaryAssetTransmission\TransmissionAdapter\AdapterInterface;
use In2code\In2publishCore\Config\ConfigContainer;
use Psr\Log\LoggerInterface;
use Throwable;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function uniqid;

/**
 * Class AssetTransmitter
 */
class AssetTransmitter implements SingletonInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var AdapterInterface
     */
    protected $adapter = null;

    /**
     * @var AdapterRegistry
     */
    protected $adapterRegistry = null;

    /**
     * @var string
     */
    protected $foreignRootPath = '';

    /**
     * AssetTransmitter constructor.
     */
    public function __construct()
    {
        $configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $this->adapterRegistry = GeneralUtility::makeInstance(AdapterRegistry::class);
        $this->foreignRootPath = rtrim($configContainer->get('foreign.rootPath'), '/');
    }

    /**
     * @param string $source Absolute local path to file(return value of
     *     \TYPO3\CMS\Core\Resource\Driver\DriverInterface::getFileForLocalProcessing)
     *
     * @return string Absolute path of the transmitted file on foreign
     */
    public function transmitTemporaryFile($source): string
    {
        $this->logger->info('Transmission of file requested', ['source' => $source]);

        if (null === $this->adapter) {
            $this->logger->debug('Lazy initializing SshAdapter');
            try {
                $adapterClass = $this->adapterRegistry->getAdapter(AdapterInterface::class);
                $this->adapter = GeneralUtility::makeInstance($adapterClass);
            } catch (Throwable $exception) {
                $this->logger->debug('SshAdapter initialization failed. See previous log for reason.');
            }
        }

        $target = $this->foreignRootPath . '/typo3temp/' . uniqid('tx_in2publishcore_temp_');

        $success = $this->adapter->copyFileToRemote($source, $target);

        if (true === $success) {
            $this->logger->debug('Successfully transferred file to foreign', ['target' => $target]);
        } else {
            $this->logger->error('Failed to transfer file to foreign', ['target' => $target]);
        }

        return $target;
    }
}
