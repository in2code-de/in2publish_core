<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command\RemoteProcedureCall;

/*
 * Copyright notice
 *
 * (c) 2019 in2code.de and the following authors:
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

use In2code\In2publishCore\Communication\RemoteProcedureCall\EnvelopeDispatcher;
use In2code\In2publishCore\Communication\RemoteProcedureCall\Letterbox;
use In2code\In2publishCore\Service\Context\ContextService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExecuteCommand extends Command
{
    const EXIT_ENVELOPE_MISSING = 230;
    const EXIT_UID_MISSING = 231;
    const EXIT_EXECUTION_FAILED = 232;
    const EXIT_WRONG_CONTEXT = 211;
    public const IDENTIFIER = 'in2publish_core:rpc:execute';
    const ARG_UID = 'uid';

    protected function configure()
    {
        $this->setDescription('Receives an envelope and executes the contained command')
             ->setHidden(true)
             ->addArgument(self::ARG_UID, InputArgument::REQUIRED, 'Uid of the envelope to execute');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
        $contextService = GeneralUtility::makeInstance(ContextService::class);
        $letterbox = GeneralUtility::makeInstance(Letterbox::class);
        $envelopeDispatcher = GeneralUtility::makeInstance(EnvelopeDispatcher::class);

        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        if (!$contextService->isForeign()) {
            $logger->warning('RPC called but context is not Foreign');
            $errOutput->writeln('This command is available on Foreign only');
            return static::EXIT_WRONG_CONTEXT;
        }

        $uid = $input->getArgument(static::ARG_UID);

        if (0 === $uid) {
            $logger->warning('RPC called but UID was not given');
            $errOutput->writeln('Please define an UID for the envelope');
            return static::EXIT_UID_MISSING;
        }

        $envelope = $letterbox->receiveEnvelope($uid, false);

        if (false === $envelope) {
            $logger->error('The requested envelope could not be received', ['uid' => $uid]);
            $errOutput->writeln('The requested envelope is not available');
            return static::EXIT_ENVELOPE_MISSING;
        }

        $success = $envelopeDispatcher->dispatch($envelope);

        $letterbox->sendEnvelope($envelope);

        if (false === $success) {
            $logger->error('Dispatching the requested envelope failed', ['uid' => $uid]);
            $errOutput->writeln('RPC failed');
            return static::EXIT_EXECUTION_FAILED;
        }

        return 0;
    }
}
