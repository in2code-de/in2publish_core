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

use In2code\In2publishCore\Cache\CachedRuntimeCacheInjection;
use In2code\In2publishCore\Cache\Exception\CacheableValueCanNotBeGeneratedException;
use In2code\In2publishCore\Command\Foreign\Status\CreateMasksCommand;
use In2code\In2publishCore\Command\Foreign\Status\DbInitQueryEncodedCommand;
use In2code\In2publishCore\Command\Foreign\Status\EncryptionKeyCommand;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcherInjection;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function base64_decode;
use function json_decode;
use function strpos;

use const JSON_THROW_ON_ERROR;

/**
 * Used to receive static information about the foreign environment like
 * configuration values or server variables
 */
class ForeignEnvironmentService implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use RemoteCommandDispatcherInjection;
    use CachedRuntimeCacheInjection;

    public function getDatabaseInitializationCommands(): string
    {
        return $this->cachedRuntimeCache->get('foreign_db_init', function (): string {
            $request = new RemoteCommandRequest(DbInitQueryEncodedCommand::IDENTIFIER);
            $response = $this->remoteCommandDispatcher->dispatch($request);

            if (!$response->isSuccessful()) {
                $this->logger->error(
                    'Could not get DB init. Falling back to empty configuration value',
                    [
                        'errors' => $response->getErrors(),
                        'exit_status' => $response->getExitStatus(),
                        'output' => $response->getOutput(),
                    ],
                );
                throw new CacheableValueCanNotBeGeneratedException('');
            }

            $encodedDbInit = 'IiI=';
            foreach ($response->getOutput() as $line) {
                if (false !== strpos($line, 'DBinit: ')) {
                    $encodedDbInit = GeneralUtility::trimExplode(':', $line)[1];
                    break;
                }
            }
            return json_decode(base64_decode($encodedDbInit), true, 512, JSON_THROW_ON_ERROR);
        });
    }

    public function getCreateMasks(): array
    {
        return $this->cachedRuntimeCache->get('create_masks', function (): ?array {
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
                    ],
                );

                $createMasks = [
                    'file' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['fileCreateMask'],
                    'folder' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['folderCreateMask'],
                ];
            }

            return $createMasks;
        });
    }

    public function getEncryptionKey(): ?string
    {
        return $this->cachedRuntimeCache->get('encryption_key', function (): ?string {
            $encryptionKey = null;

            $request = new RemoteCommandRequest(EncryptionKeyCommand::IDENTIFIER);
            $response = $this->remoteCommandDispatcher->dispatch($request);

            if ($response->isSuccessful()) {
                $values = $this->tokenizeResponse($response->getOutput());
                if (!empty($values['EKey'])) {
                    $encryptionKey = base64_decode($values['EKey']);
                }
            }
            return $encryptionKey;
        });
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
