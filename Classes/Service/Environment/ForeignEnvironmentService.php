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
            $request = GeneralUtility::makeInstance(RemoteCommandRequest::class, 'status:dbinitqueryencoded');
            $response = GeneralUtility::makeInstance(RemoteCommandDispatcher::class)->dispatch($request);

            $decodedDbInit = [];
            if ($response->isSuccessful()) {
                $encodedDbInit = 'W10=';
                foreach ($response->getOutput() as $line) {
                    if (false !== strpos($line, 'DBinit: ')) {
                        $encodedDbInit = GeneralUtility::trimExplode(':', $line)[1];
                        break;
                    }
                }
                $decodedDbInit = json_decode(base64_decode($encodedDbInit), true);
            } else {
                $this->logger->error(
                    'Could not get DB init. Falling back to empty configuration value',
                    [
                        'errors' => $response->getErrors(),
                        'exit_status' => $response->getExitStatus(),
                        'output' => $response->getOutput(),
                    ]
                );
            }

            $this->cache->set('foreign_db_init', $decodedDbInit, [], 86400);
        }
        return (array)$this->cache->get('foreign_db_init');
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     */
    public function getCreateMasks()
    {
        if (!$this->cache->has('create_masks')) {
            $request = GeneralUtility::makeInstance(RemoteCommandRequest::class, 'status:createmasks');
            $response = GeneralUtility::makeInstance(RemoteCommandDispatcher::class)->dispatch($request);

            $createMasks = null;

            if ($response->isSuccessful()) {
                $values = $this->tokenizeResponse($response->getOutput());
                if (isset($values['FileCreateMask']) && isset($values['FolderCreateMask'])) {
                    $createMasks = [
                        'file' => $values['FileCreateMask'],
                        'folder' => $values['FolderCreateMask'],
                    ];
                }
            }

            if (null === $createMasks) {
                $this->logger->error(
                    'Could not get createMasks. Falling back to local configuration value',
                    [
                        'errors' => $response->getErrors(),
                        'exit_status' => $response->getExitStatus(),
                        'output' => $response->getOutput(),
                    ]
                );

                $createMasks = [
                    'file' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'],
                    'folder' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'],
                ];
            }

            $this->cache->set('create_masks', $createMasks, [], 86400);
        }
        return (array)$this->cache->get('create_masks');
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

    /**
     * @param array $output
     * @return array
     */
    protected function tokenizeResponse(array $output)
    {
        $values = [];
        foreach ($output as $line) {
            if (false !== strpos($line, ':')) {
                list ($key, $value) = GeneralUtility::trimExplode(':', $line);
                $values[$key] = $value;
            }
        }
        return $values;
    }
}
