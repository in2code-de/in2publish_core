<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand\Type;

use In2code\In2publishCore\Component\Core\Record\Model\Node;

class SelectDemand implements Demand
{
    use UniqueRecordKeyGenerator;

    protected string $from;
    protected string $additionalWhere;
    protected string $property;
    /** @var mixed */
    protected $value;
    protected Node $record;

    /**
     * @param mixed $value
     */
    public function __construct(string $from, string $additionalWhere, string $property, $value, Node $record)
    {
        $this->from = $from;
        $this->additionalWhere = $additionalWhere;
        $this->property = $property;
        $this->value = $value;
        $this->record = $record;
    }

    public function addToDemandsArray(array &$demands): void
    {
        $uniqueRecordKey = $this->createUniqueRecordKey($this->record);
        $demands[$this->from][$this->additionalWhere][$this->property][$this->value][$uniqueRecordKey] = $this->record;
    }

    public function addToMetaArray(array &$meta, array $frame): void
    {
        $meta[$this->from][$this->additionalWhere][$this->property][] = $frame;
    }
}
