<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Environment;

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

use In2code\In2publishCore\Command\Foreign\Status\CreateMasksCommand;
use In2code\In2publishCore\Command\Foreign\Status\DbInitQueryEncodedCommand;
use In2code\In2publishCore\Command\Foreign\Status\EncryptionKeyCommand;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function base64_decode;
use function json_decode;
use function strpos;

/**
 * Used to receive static information about the foreign environment like
 * configuration values or server variables
 */
class ForeignEnvironmentService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected FrontendInterface $cache;
    protected RemoteCommandDispatcher $remoteCommandDispatcher;

    public function __construct(FrontendInterface $cache, RemoteCommandDispatcher $remoteCommandDispatcher)
    {
        $this->cache = $cache;
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
    }

    public function getDatabaseInitializationCommands(): string
    {
        if ($this->cache->has('foreign_db_init')) {
            return $this->cache->get('foreign_db_init');
        }

        $request = new RemoteCommandRequest(DbInitQueryEncodedCommand::IDENTIFIER);
        $response = $this->remoteCommandDispatcher->dispatch($request);

        $decodedDbInit = '';
        if ($response->isSuccessful()) {
            // String (two double quotes): ""
            $encodedDbInit = 'IiI=';
            foreach ($response->getOutput() as $line) {
                if (false !== strpos($line, 'DBinit: ')) {
                    $encodedDbInit = GeneralUtility::trimExplode(':', $line)[1];
                    break;
                }
            }
            $decodedDbInit = json_decode(base64_decode($encodedDbInit), true, 512, JSON_THROW_ON_ERROR);
            $this->cache->set('foreign_db_init', $decodedDbInit, [], 86400);
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

        return $decodedDbInit;
    }

    public function getCreateMasks(): array
    {
        if (!$this->cache->has('create_masks')) {
            $request = new RemoteCommandRequest(CreateMasksCommand::IDENTIFIER);
            $response = $this->remoteCommandDispatcher->dispatch($request);

            $createMasks = null;

            if ($response->isSuccessful()) {
                $values = $this->tokenizeResponse($response->getOutput());
                if (isset($values['FileCreateMask'], $values['FolderCreateMask'])) {
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

    public function getEncryptionKey(): ?string
    {
        if (!$this->cache->has('encryption_key')) {
            $encryptionKey = null;

            $request = new RemoteCommandRequest(EncryptionKeyCommand::IDENTIFIER);
            $response = $this->remoteCommandDispatcher->dispatch($request);

            if ($response->isSuccessful()) {
                $values = $this->tokenizeResponse($response->getOutput());
                if (!empty($values['EKey'])) {
                    $encryptionKey = base64_decode($values['EKey']);
                }
            }
            $this->cache->set('encryption_key', $encryptionKey, [], 86400);
        }

        return $this->cache->get('encryption_key');
    }

    protected function tokenizeResponse(array $output): array
    {
        $values = [];
        foreach ($output as $line) {
            if (false !== strpos($line, ':')) {
                [$key, $value] = GeneralUtility::trimExplode(':', $line);
                $values[$key] = $value;
            }
        }

        return $values;
    }
}
