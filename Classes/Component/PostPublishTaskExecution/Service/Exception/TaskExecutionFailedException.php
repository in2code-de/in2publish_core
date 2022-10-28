<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\PostPublishTaskExecution\Service\Exception;

use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandResponse;
use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function sprintf;

/**
 * @codeCoverageIgnore
 */
class TaskExecutionFailedException extends In2publishCoreException
{
    private const MESSAGE = "Task execution failed. Errors: \n%s\n\nOutput: \n%s";
    public const CODE = 1656947656;
    private RemoteCommandResponse $remoteCommandResponse;

    public function __construct(RemoteCommandResponse $remoteCommandResponse, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                self::MESSAGE,
                $remoteCommandResponse->getErrorsString(),
                $remoteCommandResponse->getOutputString()
            ),
            self::CODE,
            $previous
        );
        $this->remoteCommandResponse = $remoteCommandResponse;
    }

    public function getRemoteCommandResponse(): RemoteCommandResponse
    {
        return $this->remoteCommandResponse;
    }
}
