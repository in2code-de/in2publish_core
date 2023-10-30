<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand\Type;

use In2code\In2publishCore\Component\Core\Record\Model\Node;

class SysRedirectDemand implements Demand
{
    use UniqueRecordKeyGenerator;

    protected string $from;
    protected string $additionalWhere;
    protected Node $record;

    public function __construct(string $from, string $additionalWhere, Node $record)
    {
        $this->from = $from;
        $this->additionalWhere = $additionalWhere;
        $this->record = $record;
    }

    public function addToDemandsArray(array &$demands): void
    {
        $uniqueRecordKey = $this->createUniqueRecordKey($this->record);
        $demands[$this->from][$this->additionalWhere][$uniqueRecordKey] = $this->record;
    }

    public function removeFromDemandsArray(array &$demands): void
    {
        $uniqueRecordKey = $this->createUniqueRecordKey($this->record);
        unset($demands[$this->from][$this->additionalWhere][$uniqueRecordKey]);
        if (empty($demands[$this->from][$this->additionalWhere])) {
            unset($demands[$this->from][$this->additionalWhere]);
        }
        if (empty($demands[$this->from])) {
            unset($demands[$this->from]);
        }
    }

    public function addToMetaArray(array &$meta, array $frame): void
    {
        $meta[$this->from][$this->additionalWhere][] = $frame;
    }
}
