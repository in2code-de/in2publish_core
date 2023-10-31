<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand;

use In2code\In2publishCore\Component\Core\Demand\Remover\JoinDemandRemover;
use In2code\In2publishCore\Component\Core\Demand\Remover\SelectDemandRemover;
use In2code\In2publishCore\Component\Core\Demand\Type\FileDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\JoinDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\SysRedirectDemand;
use In2code\In2publishCore\Component\Core\Record\Model\Node;
use In2code\In2publishCore\Component\Core\Record\Model\Record;

use function sprintf;
use function trigger_error;

use const E_USER_DEPRECATED;

trait DeprecatedDemandsCollection
{
    /**
     * @param int|string $value
     * @deprecated Use addDemand(new SelectDemand(...)) instead
     */
    public function addSelect(
        string $from,
        string $additionalWhere,
        string $property,
        $value,
        Node $record
    ): void {
        trigger_error(
            sprintf(
                '$demands->%s is deprecated. Please use $demands->%s instead. This method will be removed in in2publish_core v13.',
                __FUNCTION__,
                'addDemand(new SelectDemand(...))',
            ),
            E_USER_DEPRECATED,
        );
        $this->addDemand(new SelectDemand($from, $additionalWhere, $property, $value, $record));
    }

    /**
     * @param string $table e.g. sys_file_storage
     * @param string $field e.g. uid
     * @param int|string $search e.g. 13
     * @deprecated Use unsetDemand(new SelectDemandRemover(...)) instead. Method will be removed in in2publish_core v13.
     */
    public function unsetSelect(string $table, string $field, $search): void
    {
        trigger_error(
            sprintf(
                '$demands->%s is deprecated. Please use $demands->%s instead. This method will be removed in in2publish_core v13.',
                __FUNCTION__,
                'unsetDemand(new SelectDemandRemover(...))',
            ),
            E_USER_DEPRECATED,
        );
        $this->unsetDemand(new SelectDemandRemover($table, $field, $search));
    }

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
    ): void {
        trigger_error(
            sprintf(
                '$demands->%s is deprecated. Please use $demands->%s instead. This method will be removed in in2publish_core v13.',
                __FUNCTION__,
                'addDemand(new JoinDemand(...))',
            ),
            E_USER_DEPRECATED,
        );
        $this->addDemand(new JoinDemand($mmTable, $joinTable, $additionalWhere, $property, $value, $record));
    }

    /**
     * @param string $mmTable e.g. tx_news_domain_model_news_related_mm
     * @param string $joinTable e.g. tx_news_domain_model_news
     * @param string $field e.g. uid_local
     * @param int|string $search e.g. 25
     * @deprecated Use addDemand(new JoinDemandRemover(...)) instead. Method will be removed in in2publish_core v13.
     */
    public function unsetJoin(string $mmTable, string $joinTable, string $field, $search): void
    {
        trigger_error(
            sprintf(
                '$demands->%s is deprecated. Please use $demands->%s instead. This method will be removed in in2publish_core v13.',
                __FUNCTION__,
                'unsetDemand(new JoinDemandRemover(...))',
            ),
            E_USER_DEPRECATED,
        );
        $this->unsetDemand(new JoinDemandRemover($mmTable, $joinTable, $field, $search));
    }

    /**
     * @deprecated Use addDemand(new FileDemand(...)) instead. Method will be removed in in2publish_core v13.
     */
    public function addFile(int $storage, string $identifier, Record $record): void
    {
        trigger_error(
            sprintf(
                '$demands->%s is deprecated. Please use $demands->%s instead. This method will be removed in in2publish_core v13.',
                __FUNCTION__,
                'addDemand(new FileDemand(...))',
            ),
            E_USER_DEPRECATED,
        );
        $this->addDemand(new FileDemand($storage, $identifier, $record));
    }

    /**
     * @return array<int, array<string, array<string, Record>>>
     * @deprecated Use getDemandsByType(FileDemand::class) instead. Method will be removed in in2publish_core v13.
     */
    public function getFiles(): array
    {
        trigger_error(
            sprintf(
                '$demands->%s is deprecated. Please use $demands->%s instead. This method will be removed in in2publish_core v13.',
                __FUNCTION__,
                'getDemandsByType(FileDemand::class)',
            ),
            E_USER_DEPRECATED,
        );
        return $this->getDemandsByType(FileDemand::class);
    }

    /**
     * @deprecated Use addDemand(new SysRedirectDemand(...)) instead. Method will be removed in in2publish_core v13.
     */
    public function addSysRedirectSelect(string $from, string $additionalWhere, Node $record): void
    {
        trigger_error(
            sprintf(
                '$demands->%s is deprecated. Please use $demands->%s instead. This method will be removed in in2publish_core v13.',
                __FUNCTION__,
                'addDemand(new SysRedirectDemand(...))',
            ),
            E_USER_DEPRECATED,
        );
        $this->addDemand(new SysRedirectDemand($from, $additionalWhere, $record));
    }

    /**
     * @return array<string, array<string, array<string, Record>>>
     * @deprecated Use getDemandsByType(SysRedirectDemand::class) instead. Method will be removed in in2publish_core
     *     v13.
     */
    public function getSysRedirectSelect(): array
    {
        trigger_error(
            sprintf(
                '$demands->%s is deprecated. Please use $demands->%s instead. This method will be removed in in2publish_core v13.',
                __FUNCTION__,
                'getDemandsByType(SysRedirectDemand::class)',
            ),
            E_USER_DEPRECATED,
        );
        return $this->getDemandsByType(SysRedirectDemand::class);
    }

    /**
     * @deprecated Use the UniqueRecordKeyGenerator trait instead.
     */
    public function uniqueRecordKey(Node $record): string
    {
        trigger_error(
            'Demands::uniqueRecordKey is deprecated. Use the UniqueRecordKeyGenerator trait instead. This method will be removed in in2publish_core v13',
            E_USER_DEPRECATED,
        );
        return $record->getClassification() . '\\' . $record->getId();
    }

    /**
     * @return array<string, array<string, array<string, array<mixed, array<string, Node>>>>>
     * @deprecated Use getDemandsByType(SelectDemand::class) instead. Method will be removed in in2publish_core v13.
     */
    public function getSelect(): array
    {
        trigger_error(
            sprintf(
                '$demands->%s is deprecated. Please use $demands->%s instead. This method will be removed in in2publish_core v13.',
                __FUNCTION__,
                'getDemandsByType(SelectDemand::class)',
            ),
            E_USER_DEPRECATED,
        );
        return $this->getDemandsByType(SelectDemand::class);
    }

    /**
     * @return array<string, array<string, array<string, array<string, array<mixed, array<string, Node>>>>>>
     * @deprecated Use getDemandsByType(JoinDemand::class) instead. Method will be removed in in2publish_core v13.
     */
    public function getJoin(): array
    {
        trigger_error(
            sprintf(
                '$demands->%s is deprecated. Please use $demands->%s instead. This method will be removed in in2publish_core v13.',
                'getJoin',
                'getDemandsByType(JoinDemand::class)',
            ),
            E_USER_DEPRECATED,
        );
        return $this->getDemandsByType(JoinDemand::class);
    }
}
