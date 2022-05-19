<?php

namespace In2code\In2publishCore\Component\TcaHandling\Demand;

use In2code\In2publishCore\Domain\Model\Node;
use In2code\In2publishCore\Domain\Model\Record;

interface Demands
{
    public function addSelect(string $from, string $additionalWhere, string $property, $value, Node $record): void;

    public function unsetSelect(string $table, string $field, $search): void;

    public function addJoin(string $mmTable, string $joinTable, string $additionalWhere, string $property, $value, Node $record): void;

    public function unsetJoin(string $mmTable, string $joinTable, string $field, $search): void;

    public function addFile(int $storage, string $identifier, Record $record): void;

    public function getFiles(): array;

    public function uniqueRecordKey(Node $record): string;

    public function getSelect(): array;

    public function getJoin(): array;
}
