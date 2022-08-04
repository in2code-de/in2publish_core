<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling\Service;

use In2code\In2publishCore\Component\Core\FileHandling\Service\Exception\EnvelopeSendingFailedException;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Component\RemoteProcedureCall\Command\Foreign\ExecuteCommand;
use In2code\In2publishCore\Component\RemoteProcedureCall\Envelope;
use In2code\In2publishCore\Component\RemoteProcedureCall\EnvelopeDispatcher;
use In2code\In2publishCore\Component\RemoteProcedureCall\Letterbox;
use In2code\In2publishCore\In2publishCoreException;
use RuntimeException;

use function is_int;
use function sprintf;

class ForeignFileSystemInfoService
{
    protected Letterbox $letterbox;
    protected RemoteCommandDispatcher $rceDispatcher;

    public function injectLetterbox(Letterbox $letterbox): void
    {
        $this->letterbox = $letterbox;
    }

    public function injectRceDispatcher(RemoteCommandDispatcher $rceDispatcher): void
    {
        $this->rceDispatcher = $rceDispatcher;
    }

    public function folderExists(int $storageUid, string $identifier): bool
    {
        $envelope = new Envelope(
            EnvelopeDispatcher::CMD_FOLDER_EXISTS,
            ['storage' => $storageUid, 'folderIdentifier' => $identifier]
        );
        return $this->executeEnvelope($envelope);
    }

    public function fileExists(int $storageUid, string $identifier): bool
    {
        $envelope = new Envelope(
            EnvelopeDispatcher::CMD_FILE_EXISTS,
            ['storage' => $storageUid, 'fileIdentifier' => $identifier]
        );
        return $this->executeEnvelope($envelope);
    }

    public function listFolderContents(int $storageUid, string $identifier): array
    {
        $envelope = new Envelope(
            EnvelopeDispatcher::CMD_LIST_FOLDER_CONTENTS,
            ['storageUid' => $storageUid, 'identifier' => $identifier]
        );
        return $this->executeEnvelope($envelope);
    }

    public function getFileInfo(array $files): array
    {
        $envelope = new Envelope(
            EnvelopeDispatcher::CMD_GET_FILE_INFO,
            ['files' => $files]
        );
        return $this->executeEnvelope($envelope);
    }

    protected function executeEnvelope(Envelope $envelope)
    {
        $uid = $this->letterbox->sendEnvelope($envelope);
        if (!is_int($uid)) {
            throw new EnvelopeSendingFailedException();
        }
        $request = new RemoteCommandRequest(ExecuteCommand::IDENTIFIER, [], [$uid]);
        $response = $this->rceDispatcher->dispatch($request);

        if (!$response->isSuccessful()) {
            throw new RuntimeException(
                sprintf(
                    'Could not execute RPC [%d]. Errors and Output: %s %s',
                    $uid,
                    $response->getErrorsString(),
                    $response->getOutputString()
                ),
                1655988997
            );
        }

        $envelope = $this->letterbox->receiveEnvelope($uid);

        if (false === $envelope) {
            throw new In2publishCoreException('Could not receive envelope [' . $uid . ']', 1655990891);
        }
        return $envelope->getResponse();
    }
}
