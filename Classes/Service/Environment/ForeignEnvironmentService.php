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

use In2code\In2publishCore\Domain\Driver\Rpc\EnvelopeDispatcher;
use In2code\In2publishCore\Security\SshConnection;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Used to receive static information about the foreign environment like configuration values or server variables
 */
class ForeignEnvironmentService
{
    /**
     * @var Registry
     */
    protected $registry = null;

    /**
     * ForeignEnvironmentService constructor.
     */
    public function __construct()
    {
        $this->registry = $this->getRegistry();
    }

    /**
     * @return array
     */
    public function getDatabaseInitializationCommands()
    {
        $dbInit = $this->registry->get('tx_in2publishcore', 'foreign_db_init', null);

        if (null === $dbInit) {
            $envelope = GeneralUtility::makeInstance(
                'In2code\\In2publishCore\\Domain\\Driver\\Rpc\\Envelope',
                EnvelopeDispatcher::CMD_GET_SET_DB_INIT
            );
            $letterbox = GeneralUtility::makeInstance('In2code\\In2publishCore\\Domain\\Driver\\Rpc\\Letterbox');
            $uid = $letterbox->sendEnvelope($envelope);
            SshConnection::makeInstance()->executeRpc($uid);
            $envelope = $letterbox->receiveEnvelope($uid);

            $dbInit = $envelope->getResponse();

            $dbInit = GeneralUtility::trimExplode(LF, str_replace('\' . LF . \'', LF, $dbInit), true);

            $this->registry->set('tx_in2publishcore', 'foreign_db_init', $dbInit);
        }
        return (array)$dbInit;
    }

    /**
     * @return Registry
     * @codeCoverageIgnore
     */
    protected function getRegistry()
    {
        return GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
    }
}
