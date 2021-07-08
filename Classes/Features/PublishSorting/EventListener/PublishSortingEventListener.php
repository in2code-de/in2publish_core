<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\PublishSorting\EventListener;

use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Features\PublishSorting\Domain\Anomaly\SortingPublisher;

class PublishSortingEventListener
{
    /** @var SortingPublisher */
    protected $sortingPublisher;

    public function __construct(SortingPublisher $sortingPublisher)
    {
        $this->sortingPublisher = $sortingPublisher;
    }

    public function onPublishingOfOneRecordBegan(PublishingOfOneRecordBegan $event): void
    {
        $this->sortingPublisher->collectSortingsToBePublished($event->getRecord());
    }

    public function onRecursiveRecordPublishingEnded(): void
    {
        $this->sortingPublisher->publishSortingRecursively();
    }
}
