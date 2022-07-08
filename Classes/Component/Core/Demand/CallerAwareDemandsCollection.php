<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Domain\Model\Node;
use In2code\In2publishCore\Domain\Model\Record;

use function debug_backtrace;

use const DEBUG_BACKTRACE_PROVIDE_OBJECT;

class CallerAwareDemandsCollection implements Demands
{
    private Demands $demand;
    private array $meta = [];

    public function __construct(Demands $demand)
    {
        $this->demand = $demand;
    }

    /**
     * @param int|string $value
     */
    public function addSelect(
        string $from,
        string $additionalWhere,
        string $property,
        $value,
        Node $record
    ): void {
        $this->demand->addSelect($from, $additionalWhere, $property, $value, $record);
        $this->collectSelectMeta($from, $additionalWhere, $property);
    }

    protected function collectSelectMeta(
        string $from,
        string $additionalWhere,
        string $property
    ): void {
        $frame = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3)[2];
        $this->meta['select'][$from][$additionalWhere][$property][] = $frame;
    }

    /**
     * @param string $table e.g. sys_file_storage
     * @param string $field e.g. uid
     * @param int|string $search e.g. 13
     * @return void
     */
    public function unsetSelect(string $table, string $field, $search): void
    {
        $this->demand->unsetSelect($table, $field, $search);
    }

    /**
     * @param int|string $value
     */
    public function addJoin(
        string $mmTable,
        string $joinTable,
        string $additionalWhere,
        string $property,
        $value,
        Node $record
    ): void {
        $this->demand->addJoin($mmTable, $joinTable, $additionalWhere, $property, $value, $record);
        $this->collectJoinMeta($mmTable, $joinTable, $additionalWhere, $property);
    }

    protected function collectJoinMeta(
        string $mmTable,
        string $joinTable,
        string $additionalWhere,
        string $property
    ): void {
        $frame = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3)[2];
        $this->meta['join'][$mmTable][$joinTable][$additionalWhere][$property][] = $frame;
    }

    /**
     * @param string $mmTable e.g. tx_news_domain_model_news_related_mm
     * @param string $joinTable e.g. tx_news_domain_model_news
     * @param string $field e.g. uid_local
     * @param int|string $search e.g. 25
     * @return void
     */
    public function unsetJoin(string $mmTable, string $joinTable, string $field, $search): void
    {
        $this->demand->unsetJoin($mmTable, $joinTable, $field, $search);
    }

    public function addFile(int $storage, string $identifier, Record $record): void
    {
        $this->demand->addFile($storage, $identifier, $record);
        $this->collectFileMeta($storage, $identifier);
    }

    protected function collectFileMeta(int $storage, string $identifier): void
    {
        $frame = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3)[2];
        $this->meta['files'][$storage][$identifier][] = $frame;
    }

    /**
     * @return array<int, array<string, array<string, Record>>>
     */
    public function getFiles(): array
    {
        return $this->demand->getFiles();
    }

    public function addSysRedirectSelect(string $from, string $additionalWhere, Node $record): void
    {
        $this->demand->addSysRedirectSelect($from, $additionalWhere, $record);
        $this->collectSysRedirectSelectMeta($from, $additionalWhere);
    }

    protected function collectSysRedirectSelectMeta(
        string $from,
        string $additionalWhere
    ): void {
        $frame = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3)[2];
        $this->meta['sysRedirectSelect'][$from][$additionalWhere][] = $frame;
    }

    public function getSysRedirectSelect(): array
    {
        return $this->demand->getSysRedirectSelect();
    }

    /**
     * @return array<string, array<string, array<string, array<mixed, array<string, Node>>>>>
     */
    public function getSelect(): array
    {
        return $this->demand->getSelect();
    }

    public function uniqueRecordKey(Node $record): string
    {
        return $this->demand->uniqueRecordKey($record);
    }

    /**
     * @return array<string, array<string, array<string, array<string, array<mixed, array<string, Node>>>>>>
     */
    public function getJoin(): array
    {
        return $this->demand->getJoin();
    }

    public function getMeta(): array
    {
        return $this->meta;
    }
}
