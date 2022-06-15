<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\RemoteProcedureCall;

use In2code\In2publishCore\Command\Foreign\RemoteProcedureCall\ExecuteCommand;
use In2code\In2publishCore\Communication\RemoteProcedureCall\Envelope;
use In2code\In2publishCore\Communication\RemoteProcedureCall\EnvelopeDispatcher;
use In2code\In2publishCore\Communication\RemoteProcedureCall\Letterbox;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ExecuteCommandTest extends UnitTestCase
{
    public function testCommandCanBeExecuted(): void
    {
        $contextService = $this->createMock(ContextService::class);
        $contextService->method('isForeign')->willReturn(true);

        $envelope = new Envelope('foo far');

        $letterbox = $this->createMock(Letterbox::class);
        $letterbox->method('receiveEnvelope')->willReturn($envelope);

        $envelopeDispatcher = $this->createMock(EnvelopeDispatcher::class);
        $envelopeDispatcher->method('dispatch')->willReturn(true);

        $input = new ArrayInput(['uid' => '16']);
        $output = new BufferedOutput();

        $command = new ExecuteCommand($contextService, $letterbox, $envelopeDispatcher);

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('', $output->fetch());
    }
}
