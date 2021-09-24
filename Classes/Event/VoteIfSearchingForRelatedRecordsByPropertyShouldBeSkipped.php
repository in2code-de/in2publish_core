<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;

final class VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped extends AbstractVotingEvent
{
    /** @var CommonRepository */
    private $commonRepository;

    /** @var RecordInterface */
    private $record;

    /** @var string */
    private $propertyName;

    /** @var array */
    private $columnConfiguration;

    public function __construct(
        CommonRepository $commonRepository,
        RecordInterface $record,
        string $propertyName,
        array $columnConfiguration
    ) {
        $this->commonRepository = $commonRepository;
        $this->record = $record;
        $this->propertyName = $propertyName;
        $this->columnConfiguration = $columnConfiguration;
    }

    public function getCommonRepository(): CommonRepository
    {
        return $this->commonRepository;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getColumnConfiguration(): array
    {
        return $this->columnConfiguration;
    }
}
