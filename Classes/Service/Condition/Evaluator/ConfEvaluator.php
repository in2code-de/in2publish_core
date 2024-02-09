<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Condition\Evaluator;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;

use function is_string;
use function str_starts_with;
use function substr;

class ConfEvaluator implements Evaluator
{
    use ConfigContainerInjection;

    public function canEvaluate($condition): bool
    {
        return is_string($condition) && str_starts_with($condition, 'CONF:');
    }

    /** @param string $condition */
    public function evaluate($condition): bool
    {
        return (bool)$this->configContainer->get(substr($condition, 5));
    }
}
