<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\RemoteProcedureCall\Command\Foreign;

use In2code\In2publishCore\Component\RemoteProcedureCall\Command\Foreign\ExecuteCommand;
use In2code\In2publishCore\Component\RemoteProcedureCall\Envelope;
use In2code\In2publishCore\Component\RemoteProcedureCall\EnvelopeDispatcher;
use In2code\In2publishCore\Component\RemoteProcedureCall\Letterbox;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\RemoteProcedureCall\Command\Foreign\ExecuteCommand
 */
class ExecuteCommandTest extends UnitTestCase
{
    /**
     * @ticket https://projekte.in2code.de/issues/51213
     * @covers ::execute
     */
    public function testCommandCanBeExecuted(): void
    {
        $this->markTestSkipped(
            'Fix test with https://projekte.in2code.de/issues/69590',
        );
        $contextService = $this->createMock(ContextService::class);
        $contextService->method('isForeign')->willReturn(true);

        $envelope = new Envelope('foo far');

        $letterbox = $this->createMock(Letterbox::class);
        $letterbox->method('receiveEnvelope')->willReturn($envelope);

        $envelopeDispatcher = $this->createMock(EnvelopeDispatcher::class);
        $envelopeDispatcher->method('dispatch')->willReturn(true);

        $input = new ArrayInput(['uid' => '16']);
        $output = new BufferedOutput();

        $envelopeDispatcher = $this->createMock(EnvelopeDispatcher::class);
        $command = new ExecuteCommand($envelopeDispatcher);
        $command->injectContextService($contextService);
        $command->injectLetterbox($letterbox);
        $command->setLogger($this->createMock(\Psr\Log\LoggerInterface::class));

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('', $output->fetch());
    }
}
