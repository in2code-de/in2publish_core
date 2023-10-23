<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Exception;

use In2code\In2publishCore\Component\RemoteCommandExecution\RemoteCommandResponse;
use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function sprintf;

/**
 * @codeCoverageIgnore
 */
class FalPublisherExecutionFailedException extends In2publishCoreException
{
    private const MESSAGE = "FalPublisherExecutionFailedException.\nOutput:\n%s\n\nErrors:\n%s";
    public const CODE = 1657191896;
    private RemoteCommandResponse $response;

    public function __construct(RemoteCommandResponse $response, Throwable $previous = null)
    {
        $this->response = $response;
        parent::__construct(
            sprintf(self::MESSAGE, $response->getOutputString(), $response->getErrorsString()),
            self::CODE,
            $previous,
        );
    }

    public function getResponse(): RemoteCommandResponse
    {
        return $this->response;
    }
}
