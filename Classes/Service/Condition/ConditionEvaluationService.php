<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Condition;

use In2code\In2publishCore\CommonInjection\ExtensionConfigurationInjection;
use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;
use In2code\In2publishCore\Service\Condition\Evaluator\Evaluator;
use In2code\In2publishCore\Service\Condition\Exception\MissingEvaluatorException;

use function is_string;

class ConditionEvaluationService
{
    use ConfigContainerInjection;
    use ExtensionConfigurationInjection;

    /** @var array<Evaluator> */
    protected array $evaluators = [];

    public function addEvaluator(Evaluator $evaluator): void
    {
        $this->evaluators[] = $evaluator;
    }

    /**
     * @param null|array<string>|string $conditions
     * @return bool
     */
    public function evaluate($conditions): bool
    {
        if (null === $conditions || [] === $conditions || '' === $conditions) {
            return true;
        }
        if (is_string($conditions)) {
            $conditions = [$conditions];
        }
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param null|array<string>|string $condition
     * @throws MissingEvaluatorException
     */
    protected function evaluateCondition($condition): bool
    {
        foreach ($this->evaluators as $evaluator) {
            if ($evaluator->canEvaluate($condition)) {
                return $evaluator->evaluate($condition);
            }
        }
        throw new MissingEvaluatorException($condition);
    }
}
