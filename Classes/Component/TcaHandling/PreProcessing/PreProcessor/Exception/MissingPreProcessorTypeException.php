<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\PreProcessor\Exception;

use In2code\In2publishCore\Component\TcaHandling\PreProcessing\TcaPreProcessor;
use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function get_class;
use function sprintf;

class MissingPreProcessorTypeException extends In2publishCoreException
{
    public const CODE = 1649243375;
    private const MESSAGE = 'You must set $this->type in your PreProcessor %s';

    protected TcaPreProcessor $tcaPreProcessor;

    public function __construct(TcaPreProcessor $tcaPreProcessor, Throwable $previous = null)
    {
        $this->tcaPreProcessor = $tcaPreProcessor;
        parent::__construct(sprintf(self::MESSAGE, get_class($tcaPreProcessor)), self::CODE, $previous);
    }

    public function getTcaPreProcessor(): TcaPreProcessor
    {
        return $this->tcaPreProcessor;
    }
}
