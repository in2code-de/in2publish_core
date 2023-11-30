<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\RemoteProcedureCall;

use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcherInjection;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Component\RemoteProcedureCall\Command\Foreign\ExecuteCommand;
use In2code\In2publishCore\Component\RemoteProcedureCall\Exception\EnvelopeSendingFailedException;
use In2code\In2publishCore\In2publishCoreException;
use RuntimeException;
use Throwable;

use function is_int;
use function sprintf;

class ExecuteCommandDispatcher
{
    use LetterboxInjection;
    use RemoteCommandDispatcherInjection;

    /**
     * @return mixed
     * @throws EnvelopeSendingFailedException
     * @throws In2publishCoreException
     * @throws Throwable
     */
    public function executeEnvelope(Envelope $envelope)
    {
        $uid = $this->letterbox->sendEnvelope($envelope);
        if (!is_int($uid)) {
            throw new EnvelopeSendingFailedException();
        }
        $request = new RemoteCommandRequest(ExecuteCommand::IDENTIFIER, [], [$uid]);
        $response = $this->remoteCommandDispatcher->dispatch($request);

        if (!$response->isSuccessful()) {
            throw new RuntimeException(
                sprintf(
                    'Could not execute RPC [%d]. Errors and Output: %s %s',
                    $uid,
                    $response->getErrorsString(),
                    $response->getOutputString(),
                ),
                1699621336,
            );
        }

        $envelope = $this->letterbox->receiveEnvelope($uid);

        if (false === $envelope) {
            throw new In2publishCoreException('Could not receive envelope [' . $uid . ']', 1699641778);
        }
        return $envelope->getResponse();
    }
}
