<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;

final class VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped extends AbstractVotingEvent
{
    /** @var CommonRepository */
    private $commonRepository;

    /** @var RecordInterface */
    private $record;

    /** @var string */
    private $column;

    /** @var array */
    private $columnConfiguration;

    /** @var array */
    private $flexFormDefinition;

    /** @var array */
    private $flexFormData;

    public function __construct(
        CommonRepository $commonRepository,
        RecordInterface $record,
        string $column,
        array $columnConfiguration,
        array $flexFormDefinition,
        array $flexFormData
    ) {
        $this->commonRepository = $commonRepository;
        $this->record = $record;
        $this->column = $column;
        $this->columnConfiguration = $columnConfiguration;
        $this->flexFormDefinition = $flexFormDefinition;
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

    public function getColumnConfiguration(): array
    {
        return $this->columnConfiguration;
    }

    public function getFlexFormDefinition(): array
    {
        return $this->flexFormDefinition;
    }

    public function getFlexFormData(): array
    {
        return $this->flexFormData;
    }
}
