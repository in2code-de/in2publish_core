<?php
namespace In2code\In2publishCore\Command;

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
use In2code\In2publishCore\Domain\Driver\Rpc\Letterbox;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RpcCommandController
 */
class RpcCommandController extends AbstractCommandController
{
    const EXIT_ENVELOPE_MISSING = 2300;
    const EXIT_UID_MISSING = 2301;
    const EXIT_EXECUTION_FAILED = 2302;
    const EXECUTE_COMMAND = 'rpc:execute';

    /**
     * @var Letterbox
     */
    protected $letterbox = null;

    /**
     * @var EnvelopeDispatcher
     */
    protected $envelopeDispatcher = null;

    /**
     * RpcCommandController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->letterbox = GeneralUtility::makeInstance(Letterbox::class);
        $this->envelopeDispatcher = GeneralUtility::makeInstance(
            EnvelopeDispatcher::class
        );
    }

    /**
     * @param int $uid Envelope identifier
     * @internal
     */
    public function executeCommand($uid = 0)
    {
        if (!$this->contextService->isForeign()) {
            $this->logger->warning('RPC called but context is not Foreign');
            $this->outputLine('This command is available on Foreign only');
            $this->sendAndExit(static::EXIT_WRONG_CONTEXT);
        }

        if (0 === $uid) {
            $this->logger->warning('RPC called but UID was not given');
            $this->outputLine('Please define an UID for the envelope');
            $this->sendAndExit(static::EXIT_UID_MISSING);
        }

        $envelope = $this->letterbox->receiveEnvelope($uid, false);

        if (false === $envelope) {
            $this->logger->error('The requested envelope could not be received', ['uid' => $uid]);
            $this->outputLine('The requested envelope is not available');
            $this->sendAndExit(static::EXIT_ENVELOPE_MISSING);
        }

        $success = $this->envelopeDispatcher->dispatch($envelope);

        $this->letterbox->sendEnvelope($envelope);

        if (false === $success) {
            $this->logger->error('Dispatching the requested envelope failed', ['uid' => $uid]);
            $this->outputLine('RPC failed');
            $this->sendAndExit(static::EXIT_EXECUTION_FAILED);
        }
    }
}
