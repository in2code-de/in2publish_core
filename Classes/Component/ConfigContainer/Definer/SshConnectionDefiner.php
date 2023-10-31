<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Definer;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
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

use In2code\In2publishCore\Component\ConfigContainer\Builder;
use In2code\In2publishCore\Component\ConfigContainer\ConditionalConfigServiceInterface;
use In2code\In2publishCore\Component\ConfigContainer\Node\NodeCollection;
use In2code\In2publishCore\Component\ConfigContainer\Validator\FileExistsValidator as FEV;
use In2code\In2publishCore\Component\ConfigContainer\Validator\IPv4PortValidator;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteAdapter\RemoteAdapterRegistryInjection;
use In2code\In2publishCore\Component\TemporaryAssetTransmission\TransmissionAdapter\TransmissionAdapterRegistryInjection;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;

class SshConnectionDefiner implements DefinerServiceInterface, ConditionalConfigServiceInterface
{
    use ContextServiceInjection;
    use RemoteAdapterRegistryInjection;
    use TransmissionAdapterRegistryInjection;

    public function isEnabled(): bool
    {
        // Require extending classes to override this method
        if (static::class !== self::class) {
            return false;
        }
        return $this->contextService->isForeign()
            || 'ssh' === $this->remoteAdapterRegistry->getSelectedAdapter()
            || 'ssh' === $this->transmissionAdapterRegistry->getSelectedAdapter();
    }

    public function getLocalDefinition(): NodeCollection
    {
        return Builder::start()
                      ->addArray(
                          'sshConnection',
                          Builder::start()
                                 ->addString('host', 'www.example.com')
                                 ->addInteger('port', 22, [IPv4PortValidator::class])
                                 ->addString('username', 'ssh-account')
                                 ->addString('privateKeyFileAndPathName', '/home/ssh-account/.ssh/id_rsa', [FEV::class])
                                 ->addString(
                                     'publicKeyFileAndPathName',
                                     '/home/ssh-account/.ssh/id_rsa.pub',
                                     [FEV::class],
                                 )
                                 ->addOptionalString('privateKeyPassphrase', '')
                                 ->addBoolean('enableForeignKeyFingerprintCheck', true)
                                 ->addString('foreignKeyFingerprint', '00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00')
                                 ->addString('foreignKeyFingerprintHashingMethod', 'SSH2_FINGERPRINT_MD5')
                                 ->addBoolean('ignoreChmodFail', false),
                      )
                      ->addArray(
                          'debug',
                          Builder::start()
                                 ->addBoolean('showForeignKeyFingerprint', false),
                      )
                      ->end();
    }

    public function getForeignDefinition(): NodeCollection
    {
        return Builder::start()->end();
    }
}
