<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Config\Definer;

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

use In2code\In2publishCore\Config\Builder;
use In2code\In2publishCore\Config\Node\NodeCollection;
use In2code\In2publishCore\Config\Validator\FileExistsValidator as FEV;
use In2code\In2publishCore\Config\Validator\HostNameValidator;
use In2code\In2publishCore\Config\Validator\IPv4PortValidator;

/**
 * Class SshConnectionDefiner
 */
class SshConnectionDefiner implements DefinerInterface
{
    /**
     * @return NodeCollection
     */
    public function getLocalDefinition()
    {
        return Builder::start()
                      ->addArray(
                          'sshConnection',
                          Builder::start()
                                 ->addString('host', 'www.example.com', [HostNameValidator::class => [22]])
                                 ->addInteger('port', 22, [IPv4PortValidator::class])
                                 ->addString('username', 'ssh-account')
                                 ->addString('privateKeyFileAndPathName', '/home/ssh-account/.ssh/id_rsa', [FEV::class])
                                 ->addString(
                                     'publicKeyFileAndPathName',
                                     '/home/ssh-account/.ssh/id_rsa.pub',
                                     [FEV::class]
                                 )
                                 ->addOptionalString('privateKeyPassphrase', '')
                                 ->addString('foreignKeyFingerprint', '00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:00')
                                 ->addString('foreignKeyFingerprintHashingMethod', 'SSH2_FINGERPRINT_MD5')
                                 ->addBoolean('ignoreChmodFail', false)
                      )
                      ->end();
    }

    /**
     * @return NodeCollection
     */
    public function getForeignDefinition()
    {
        return Builder::start()->end();
    }
}
