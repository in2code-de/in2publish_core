<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Condition;

/**
 * @codeCoverageIgnore
 */
trait ConditionEvaluationServiceInjection
{
    protected ConditionEvaluationService $conditionEvaluationService;

    /**
     * @noinspection PhpUnused
     */
    public function injectConditionEvaluationService(ConditionEvaluationService $conditionEvaluationService): void
    {
        $this->conditionEvaluationService = $conditionEvaluationService;
    }
}
