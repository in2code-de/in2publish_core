<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\Publisher;

use Exception;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Domain\Model\FolderRecord;
use In2code\In2publishCore\Domain\Model\Record;
use TYPO3\CMS\Core\Database\Connection;

use function bin2hex;
use function random_bytes;

class FolderRecordPublisher implements Publisher, FinishablePublisher
{
    // All A_* constant values must be 6 chars
    public const A_DELETE = 'delete';
    public const A_INSERT = 'insert';
    protected string $requestToken;
    protected Connection $foreignDatabase;
    protected RemoteCommandDispatcher $remoteCommandDispatcher;
    protected bool $hasTasks = false;

    public function __construct()
    {
        $this->requestToken = bin2hex(random_bytes(16));
    }

    public function injectForeignDatabase(Connection $foreignDatabase): void
    {
        $this->foreignDatabase = clone $foreignDatabase;
        $this->foreignDatabase->close();
        $this->foreignDatabase->connect();
    }

    public function injectRemoteCommandDispatcher(RemoteCommandDispatcher $remoteCommandDispatcher): void
    {
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
    }

    public function canPublish(Record $record): bool
    {
        return $record instanceof FolderRecord;
    }

    public function publish(Record $record)
    {
        if ($record->getState() === Record::S_DELETED) {
            $this->hasTasks = true;
            $this->foreignDatabase->insert('tx_in2publishcore_filepublisher_task', [
                'request_token' => $this->requestToken,
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'storage_uid' => $record->getForeignProps()['storage'],
                'identifier' => $record->getForeignProps()['combinedIdentifier'],
                'identifier_hash' => '',
                'folder_action' => self::A_DELETE,
            ]);
            return;
        }
        if ($record->getState() === Record::S_ADDED) {
            $this->hasTasks = true;
            $this->foreignDatabase->insert('tx_in2publishcore_filepublisher_task', [
                'request_token' => $this->requestToken,
                'crdate' => $GLOBALS['EXEC_TIME'],
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'storage_uid' => $record->getLocalProps()['storage'],
                'identifier' => $record->getLocalProps()['combinedIdentifier'],
                'identifier_hash' => '',
                'folder_action' => self::A_INSERT,
            ]);
        }
    }

    public function finish(): void
    {
        if ($this->hasTasks) {
            $request = new RemoteCommandRequest('in2publish_core:tcahandling:falpublisher', [], [$this->requestToken]);
            $response = $this->remoteCommandDispatcher->dispatch($request);
            if (!$response->isSuccessful()) {
                throw new Exception($response->getErrorsString() . $response->getOutputString());
            }
        }
    }
}
