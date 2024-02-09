<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand\Remover;

use In2code\In2publishCore\Component\Core\Demand\Type\JoinDemand;

use function array_keys;

class JoinDemandRemover implements DemandRemover
{
    protected string $mmTable;
    protected string $joinTable;
    protected string $field;
    /** @var mixed */
    protected $search;

    /**
     * @param mixed $search
     */
    public function __construct(string $mmTable, string $joinTable, string $field, $search)
    {
        $this->mmTable = $mmTable;
        $this->joinTable = $joinTable;
        $this->field = $field;
        $this->search = $search;
    }

    public function getDemandType(): string
    {
        return JoinDemand::class;
    }

    public function removeFromDemandsArray(array &$demands): void
    {
        foreach ($demands[$this->mmTable][$this->joinTable] ?? [] as $additionalWhere => $properties) {
            foreach (array_keys($properties) as $property) {
                if ($property === $this->field) {
                    unset($demands[$this->mmTable][$this->joinTable][$additionalWhere][$property][$this->search]);
                }
                if (empty($demands[$this->mmTable][$this->joinTable][$additionalWhere][$property])) {
                    unset($demands[$this->mmTable][$this->joinTable][$additionalWhere][$property]);
                }
            }
            if (empty($demands[$this->mmTable][$this->joinTable][$additionalWhere])) {
                unset($demands[$this->mmTable][$this->joinTable][$additionalWhere]);
            }
        }
        if (empty($demands[$this->mmTable][$this->joinTable])) {
            unset($demands[$this->mmTable][$this->joinTable]);
        }
        if (empty($demands[$this->mmTable])) {
            unset($demands[$this->mmTable]);
        }
    }
}
