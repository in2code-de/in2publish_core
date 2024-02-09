<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Service\Condition\Evaluator;

use In2code\In2publishCore\CommonInjection\ExtensionConfigurationInjection;
use Throwable;

use function explode;
use function is_string;
use function str_contains;
use function substr;

class ExtConfEvaluator implements Evaluator
{
    use ExtensionConfigurationInjection;

    public function canEvaluate($condition): bool
    {
        if (!is_string($condition)) {
            return false;
        }
        if (!str_starts_with($condition, 'EXTCONF:')) {
            return false;
        }
        $reference = substr($condition, 8);
        if (!str_contains(substr($reference, 1), ':')) {
            return false;
        }
        [$extKey, $configPath] = explode(':', $reference, 2);
        try {
            $this->extensionConfiguration->get($extKey, $configPath);
        } catch (Throwable $exception) {
            return false;
        }
        return true;
    }

    /** @param string $condition */
    public function evaluate($condition): bool
    {
        $reference = substr($condition, 8);
        [$extKey, $configPath] = explode(':', $reference, 2);
        return (bool)$this->extensionConfiguration->get($extKey, $configPath);
    }
}
