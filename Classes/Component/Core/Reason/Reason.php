<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Reason;

use Throwable;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class Reason
{
    private string $label;
    private array $labelArguments;
    private array $context;

    public function __construct(string $label, array $labelArguments = [], array $context = [])
    {
        $this->label = $label;
        $this->labelArguments = $labelArguments;
        $this->context = $context;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getLabelArguments(): array
    {
        return $this->labelArguments;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getReadableLabel(): string
    {
        try {
            return LocalizationUtility::translate($this->label, null, $this->labelArguments);
        } catch (Throwable $exception) {
            return $this->label;
        }
    }

    public function __toString()
    {
        return $this->getReadableLabel();
    }
}
