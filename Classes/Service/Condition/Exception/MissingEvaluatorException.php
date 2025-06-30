<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Condition\Exception;

use In2code\In2publishCore\In2publishCoreException;
use Throwable;

use function is_array;
use function sprintf;
use function var_export;

class MissingEvaluatorException extends In2publishCoreException
{
    protected const MESSAGE = 'No evaluator found to evaluate condition: "%s"';
    public const CODE = 1701786012;
    /** @var array|string|null */
    protected $condition;


    public function __construct(array|string|null $condition, ?Throwable $previous = null)
    {
        $this->condition = $condition;
        if (is_array($condition)) {
            $condition = var_export($condition, true);
        }
        parent::__construct(sprintf(self::MESSAGE, $condition), self::CODE, $previous);
    }

    /** @return array|string|null */
    public function getCondition()
    {
        return $this->condition;
    }
}
