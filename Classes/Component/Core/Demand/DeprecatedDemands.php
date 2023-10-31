<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Component\Core\Record\Model\Node;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

interface DeprecatedDemands
{
    /**
     * @param int|string $value
     * @deprecated Use addDemand(new SelectDemand(...)) instead. Method will be removed in in2publish_core v13.
     */
    public function addSelect(string $from, string $additionalWhere, string $property, $value, Node $record): void;

    /**
     * @param int|string $search $search
     * @deprecated Use unsetDemand(new SelectDemandRemover(...)) instead. Method will be removed in in2publish_core v13.
     */
    public function unsetSelect(string $table, string $field, $search): void;

    /**
     * @param int|string $value
     * @deprecated Use addDemand(new JoinDemand(...)) instead. Method will be removed in in2publish_core v13.
     */
    public function addJoin(
        string $mmTable,
        string $joinTable,
        string $additionalWhere,
        string $property,
        $value,
        Node $record
    ): void;

    /**
     * @param mixed $search
     * @deprecated Use addDemand(new JoinDemandRemover(...)) instead. Method will be removed in in2publish_core v13.
     */
    public function unsetJoin(string $mmTable, string $joinTable, string $field, $search): void;

    /**
     * @deprecated Use addDemand(new FileDemand(...)) instead. Method will be removed in in2publish_core v13.
     */
    public function addFile(int $storage, string $identifier, Record $record): void;

    /**
     * @return array<int, array<string, array<string, Record>>>
     * @deprecated Use getDemandsByType(FileDemand::class) instead. Method will be removed in in2publish_core v13.
     */
    public function getFiles(): array;

    /**
     * @deprecated Use addDemand(new SysRedirectDemand(...)) instead. Method will be removed in in2publish_core v13.
     */
    public function addSysRedirectSelect(string $from, string $additionalWhere, Node $record): void;

    /**
     * @return array<string, array<string, array<string, Record>>>
     * @deprecated Use getDemandsByType(SysRedirectDemand::class) instead. Method will be removed in in2publish_core
     *     v13.
     */
    public function getSysRedirectSelect(): array;

    /**
     * @deprecated Use the UniqueRecordKeyGenerator trait instead.
     */
    public function uniqueRecordKey(Node $record): string;

    /**
     * @return array<string, array<string, array<string, array<mixed, array<string, Node>>>>>
     * @deprecated Use getDemandsByType(SelectDemand::class) instead. Method will be removed in in2publish_core v13.
     */
    public function getSelect(): array;

    /**
     * @deprecated Use getDemandsByType(JoinDemand::class) instead. Method will be removed in in2publish_core v13.
     */
    public function getJoin(): array;
}
