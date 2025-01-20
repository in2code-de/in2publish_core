<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher;

use In2code\In2publishCore\CommonInjection\ForeignDatabaseReconnectedInjection;
use In2code\In2publishCore\Component\Core\Publisher\Exception\FalPublisherExecutionFailedException;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandDispatcherInjection;
use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandRequest;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function bin2hex;
use function get_class;
use function implode;
use function json_encode;
use function random_bytes;

use const JSON_THROW_ON_ERROR;

abstract class AbstractFilesystemPublisher implements Publisher, TransactionalPublisher
{
    use ForeignDatabaseReconnectedInjection;
    use RemoteCommandDispatcherInjection;

    protected array $instructions = [];

    public function start(): void
    {
        if (!$this->foreignDatabase->isTransactionActive()) {
            $this->foreignDatabase->beginTransaction();
        }
    }

    public function cancel(): void
    {
        if ($this->foreignDatabase->isTransactionActive()) {
            $this->foreignDatabase->rollBack();
        }
    }

    public function finish(): void
    {
        if (empty($this->instructions)) {
            if ($this->foreignDatabase->isTransactionActive()) {
                $this->foreignDatabase->commit();
            }
            return;
        }

        $requestTokens = [];
        $instructions = $this->instructions;
        $this->instructions = [];
        $data = [];
        foreach ($instructions as $instruction) {
            $requestTokens[] = $requestToken = bin2hex(random_bytes(16));
            $class = get_class($instruction);
            $configuration = json_encode($instruction->getConfiguration(), JSON_THROW_ON_ERROR);
            $data[] = [
                'request_token' => $requestToken,
                'crdate' => GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp'),
                'tstamp' => GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp'),
                'instruction' => $class,
                'configuration' => $configuration,
            ];
        }

        $this->foreignDatabase->bulkInsert('tx_in2publishcore_filepublisher_instruction', $data);
        if ($this->foreignDatabase->isTransactionActive()) {
            $this->foreignDatabase->commit();
        }

        $request = new RemoteCommandRequest('in2publish_core:core:falpublisher', [], [implode(',', $requestTokens)]);
        $response = $this->remoteCommandDispatcher->dispatch($request);
        if (!$response->isSuccessful()) {
            throw new FalPublisherExecutionFailedException($response, 1818232345);
        }
    }
}
