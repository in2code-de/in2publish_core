<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;

final class VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped extends AbstractVotingEvent
{
    /** @var CommonRepository */
    private $commonRepository;

    /** @var RecordInterface */
    private $record;

    /** @var string */
    private $column;

    /** @var string */
    private $key;

    /** @var array */
    private $config;

    /** @var mixed */
    private $flexFormData;

    public function __construct(
        CommonRepository $commonRepository,
        RecordInterface $record,
        string $column,
        string $key,
        array $config,
        $flexFormData
    ) {
        $this->commonRepository = $commonRepository;
        $this->record = $record;
        $this->column = $column;
        $this->key = $key;
        $this->config = $config;
        $this->flexFormData = $flexFormData;
    }

    public function getCommonRepository(): CommonRepository
    {
        return $this->commonRepository;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getFlexFormData()
    {
        return $this->flexFormData;
    }
}
