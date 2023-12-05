<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Condition\Evaluator;

interface Evaluator
{
    /** @param null|array<string>|string $condition */
    public function canEvaluate($condition): bool;

    /** @param null|array<string>|string $condition */
    public function evaluate($condition): bool;
}
