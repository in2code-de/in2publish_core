<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\CommonInjection\ForeignDatabaseReconnectedInjection;
use In2code\In2publishCore\Component\Core\Publisher\Exception\FalPublisherExecutionFailedException;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcherInjection;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;

use function bin2hex;
use function get_class;
use function json_encode;
use function random_bytes;

abstract class AbstractFilesystemPublisher implements Publisher, FinishablePublisher
{
    use ForeignDatabaseReconnectedInjection;
    use RemoteCommandDispatcherInjection;

    protected string $requestToken;
    protected array $instructions = [];

    public function __construct()
    {
        $this->requestToken = bin2hex(random_bytes(16));
    }

    public function finish(): void
    {
        if (!empty($this->instructions)) {
            $instructions = $this->instructions;
            $this->instructions = [];
            $data = [];
            foreach ($instructions as $instruction) {
                $class = get_class($instruction);
                $configuration = json_encode($instruction->getConfiguration(), JSON_THROW_ON_ERROR);
                $data[] = [
                    'request_token' => $this->requestToken,
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'tstamp' => $GLOBALS['EXEC_TIME'],
                    'instruction' => $class,
                    'configuration' => $configuration,
                ];
            }

            $this->foreignDatabase->bulkInsert('tx_in2publishcore_filepublisher_instruction', $data);

            $request = new RemoteCommandRequest('in2publish_core:core:falpublisher', [], [$this->requestToken]);
            $response = $this->remoteCommandDispatcher->dispatch($request);
            if (!$response->isSuccessful()) {
                throw new FalPublisherExecutionFailedException($response);
            }
        }
    }
}
