<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling\Service;

use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Component\Core\FileHandling\Service\Exception\ForeignFileInfoExecutionFailedException;
use TYPO3\CMS\Core\Database\Connection;

use function array_keys;
use function bin2hex;
use function hash;
use function random_bytes;

class ForeignFileInfoService
{
    protected Connection $foreignDatabase;
    protected RemoteCommandDispatcher $remoteCommandDispatcher;

    public function injectForeignConnection(Connection $foreignDatabase): void
    {
        $this->foreignDatabase = $foreignDatabase;
    }

    public function injectRemoteCommandDispatcher(RemoteCommandDispatcher $remoteCommandDispatcher): void
    {
        $this->remoteCommandDispatcher = $remoteCommandDispatcher;
    }

    public function addFileInfoToFiles(array $files): array
    {
        $requestToken = bin2hex(random_bytes(16));

        $data = [];
        foreach ($files as $storage => $identifiers) {
            foreach (array_keys($identifiers) as $identifier) {
                $data[] = [
                    $storage,
                    $identifier,
                    hash('sha1', $identifier),
                    $requestToken,
                    $GLOBALS['EXEC_TIME'],
                ];
            }
        }
        $this->foreignDatabase->bulkInsert(
            'tx_in2publishcore_remotefaldriver_file',
            $data,
            [
                'storage_uid',
                'identifier',
                'identifier_hash',
                'request_token',
                'crdate',
            ]
        );

        $remoteCommand = new RemoteCommandRequest('in2publish_core:core:foreignfileinfo', [], [$requestToken]);
        $response = $this->remoteCommandDispatcher->dispatch($remoteCommand);

        if (!$response->isSuccessful()) {
            throw new ForeignFileInfoExecutionFailedException($response);
        }

        $rows = $this->foreignDatabase->select(
            ['*'],
            'tx_in2publishcore_remotefaldriver_file',
            ['request_token' => $requestToken]
        );

        foreach ($rows as $row) {
            $storage = $row['storage_uid'];
            $identifier = $row['identifier'];

            if (null !== $row['attr_name']) {
                $files[$storage][$identifier]['props'] = [
                    'storage' => $storage,
                    'identifier' => $row['identifier'],
                    'identifier_hash' => $row['identifier_hash'],
                    'size' => $row['attr_size'],
                    'mimetype' => $row['attr_mimetype'],
                    'name' => $row['attr_name'],
                    'extension' => $row['attr_extension'],
                    'folder_hash' => $row['attr_folder_hash'],
                ];
            }
        }
        return $files;
    }
}
