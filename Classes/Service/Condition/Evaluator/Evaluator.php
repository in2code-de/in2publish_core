<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Condition\Evaluator;

interface Evaluator
{
    /** @param array<string>|string|null $condition */
    public function canEvaluate($condition): bool;

    /** @param array<string>|string|null $condition */
    public function evaluate($condition): bool;
}
