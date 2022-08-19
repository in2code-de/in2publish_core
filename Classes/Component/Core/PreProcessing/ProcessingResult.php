<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing;

class ProcessingResult
{
    public const COMPATIBLE = 0;
    public const INCOMPATIBLE = 1;
    private int $result;
    /** @var mixed */
    private $value;

    /**
     * @param int $result self::COMPATIBLE or self::INCOMPATIBLE
     */
    public function __construct(int $result, $value)
    {
        $this->result = $result;
        $this->value = $value;
    }

    public function isCompatible(): bool
    {
        return self::COMPATIBLE === $this->result;
    }

    public function getValue()
    {
        return $this->value;
    }
}
