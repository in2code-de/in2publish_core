<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling\Service\Exception;

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

/**
 * @codeCoverageIgnore
 */
class EnvelopeSendingFailedException extends In2publishCoreException
{
    private const MESSAGE = 'Sending the envelope for the foreign file info service failed.';
    public const CODE = 1657192080;

    public function __construct(Throwable $previous = null)
    {
        parent::__construct(self::MESSAGE, self::CODE, $previous);
    }
}
