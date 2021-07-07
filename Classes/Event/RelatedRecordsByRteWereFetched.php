<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Event;

use In2code\In2publishCore\Domain\Repository\CommonRepository;

final class RelatedRecordsByRteWereFetched
{
    /** @var CommonRepository */
    private $commonRepository;

    /** @var string */
    private $bodyText;

    /** @var array */
    private $excludedTableNames;

    /** @var array */
    private $relatedRecords;

    public function __construct(
        CommonRepository $commonRepository,
        string $bodyText,
        array $excludedTableNames,
        array &$relatedRecords
    ) {
        $this->commonRepository = $commonRepository;
        $this->bodyText = $bodyText;
        $this->excludedTableNames = $excludedTableNames;
        $this->relatedRecords = &$relatedRecords;
    }

    public function getCommonRepository(): CommonRepository
    {
        return $this->commonRepository;
    }

    public function getBodyText(): string
    {
        return $this->bodyText;
    }

    public function getExcludedTableNames(): array
    {
        return $this->excludedTableNames;
    }

    public function getRelatedRecords(): array
    {
        return $this->relatedRecords;
    }
}
