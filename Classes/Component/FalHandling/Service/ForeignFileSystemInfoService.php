<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\FalHandling\Service;

use Exception;
use In2code\In2publishCore\Command\Foreign\RemoteProcedureCall\ExecuteCommand;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Communication\RemoteProcedureCall\Envelope;
use In2code\In2publishCore\Communication\RemoteProcedureCall\EnvelopeDispatcher;
use In2code\In2publishCore\Communication\RemoteProcedureCall\Letterbox;
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
        $folderInfo = $this->executeEnvelope($envelope);
        return $folderInfo['exists'];
    }

    public function fileExists(int $storageUid, string $identifier): bool
    {
        $envelope = new Envelope(
            EnvelopeDispatcher::CMD_FILE_EXISTS,
            ['storage' => $storageUid, 'fileIdentifier' => $identifier]
        );
        $folderInfo = $this->executeEnvelope($envelope);
        return isset($folderInfo[$identifier]);
    }

    public function listFolderContents(int $storageUid, string $identifier): array
    {
        $envelope = new Envelope(
            EnvelopeDispatcher::CMD_LIST_FOLDER_CONTENTS,
            ['storageUid' => $storageUid, 'identifier' => $identifier]
        );
        return $this->executeEnvelope($envelope);
    }

    protected function executeEnvelope(Envelope $envelope)
    {
        $uid = $this->letterbox->sendEnvelope($envelope);
        if (!is_int($uid)) {
            throw new Exception('Could not send envelope');
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
