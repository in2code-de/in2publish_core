<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Domain\Service\Exception;

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function implode;
use function sprintf;

use const PHP_EOL;

class AllSitesCommandException extends In2publishCoreException
{
    protected const MESSAGE = 'Exception during the fetching of all foreign sites. Code [%d]: Errors: "%s"; Outout: "%s".';
    public const CODE = 1631616241;

    /** @var array */
    private $errors;

    /** @var array */
    private $output;

    public function __construct(int $code, array $errors, array $output, Throwable $previous = null)
    {
        $this->code = $code;
        $this->errors = $errors;
        $this->output = $output;
        parent::__construct(
            sprintf(self::MESSAGE, $code, implode(PHP_EOL, $errors), implode(PHP_EOL, $output)),
            $code,
            $previous
        );
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getOutput(): array
    {
        return $this->output;
    }
}
