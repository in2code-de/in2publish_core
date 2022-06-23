<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Foreign\RemoteProcedureCall;

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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const ARG_UID = 'uid';
    public const ARG_UID_DESCRIPTION = 'Uid of the envelope to execute';
    public const EXIT_ENVELOPE_MISSING = 230;
    public const EXIT_UID_MISSING = 231;
    public const EXIT_EXECUTION_FAILED = 232;
    public const IDENTIFIER = 'in2publish_core:rpc:execute';
    protected ContextService $contextService;
    protected Letterbox $letterbox;
    protected EnvelopeDispatcher $envelopeDispatcher;

    public function injectContextService(ContextService $contextService): void
    {
        $this->contextService = $contextService;
    }

    public function injectLetterbox(Letterbox $letterbox): void
    {
        $this->letterbox = $letterbox;
    }

    public function injectEnvelopeDispatcher(EnvelopeDispatcher $envelopeDispatcher): void
    {
        $this->envelopeDispatcher = $envelopeDispatcher;
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_UID, InputArgument::REQUIRED, self::ARG_UID_DESCRIPTION);
    }

    public function isEnabled(): bool
    {
        return $this->contextService->isForeign();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $uid = (int)$input->getArgument(static::ARG_UID);

        if (0 === $uid) {
            $this->logger->warning('RPC called but UID was not given');
            $errOutput->writeln('Please define an UID for the envelope');
            return static::EXIT_UID_MISSING;
        }

        $envelope = $this->letterbox->receiveEnvelope($uid, false);

        if (false === $envelope) {
            $this->logger->error('The requested envelope could not be received', ['uid' => $uid]);
            $errOutput->writeln('The requested envelope is not available');
            return static::EXIT_ENVELOPE_MISSING;
        }

        $success = $this->envelopeDispatcher->dispatch($envelope);

        $this->letterbox->sendEnvelope($envelope);

        if (false === $success) {
            $this->logger->error('Dispatching the requested envelope failed', ['uid' => $uid]);
            $errOutput->writeln('RPC failed');
            return static::EXIT_EXECUTION_FAILED;
        }

        return Command::SUCCESS;
    }
}
