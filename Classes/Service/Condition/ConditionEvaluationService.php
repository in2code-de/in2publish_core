<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Condition;

use In2code\In2publishCore\CommonInjection\ExtensionConfigurationInjection;
use In2code\In2publishCore\Component\ConfigContainer\ConfigContainerInjection;
use In2code\In2publishCore\Service\Condition\Evaluator\Evaluator;
use In2code\In2publishCore\Service\Condition\Exception\MissingEvaluatorException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @param array<string>|string|null $conditions
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
            // Workaround for TYPO3 v11, where tag attributes must be strings
            // and arrays are not allowed. Will be removed when TYPO3 v11 support is dropped.
            $combinedExpressions = GeneralUtility::trimExplode(' AND ', $condition);
            foreach ($combinedExpressions as $expression) {
                if (!$this->evaluateCondition($expression)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param array<string>|string|null $condition
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
