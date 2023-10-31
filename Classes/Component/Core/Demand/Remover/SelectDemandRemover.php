<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand\Remover;

use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;

class SelectDemandRemover implements DemandRemover
{
    private string $from;
    private string $field;
    /** @var mixed */
    private $search;

    /**
     * @param mixed $search
     */
    public function __construct(string $from, string $field, $search)
    {
        $this->from = $from;
        $this->field = $field;
        $this->search = $search;
    }

    public function getDemandType(): string
    {
        return SelectDemand::class;
    }

    public function removeFromDemandsArray(array &$demands): void
    {
        foreach ($demands[$this->from] ?? [] as $additionalWhere => $properties) {
            foreach ($properties as $property => $values) {
                if ($property === $this->field) {
                    unset($demands[$this->from][$additionalWhere][$property][$this->search]);
                }
                if (empty($demands[$this->from][$additionalWhere][$property])) {
                    unset($demands[$this->from][$additionalWhere][$property]);
                }
            }
            if (empty($demands[$this->from][$additionalWhere])) {
                unset($demands[$this->from][$additionalWhere]);
            }
        }
        if (empty($demands[$this->from])) {
            unset($demands[$this->from]);
        }
    }
}
