<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RefIndexUpdate\EventListener;

use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Features\RefIndexUpdate\Domain\Anomaly\RefIndexUpdater;

class RefIndexUpdateEventListener
{
    /** @var RefIndexUpdater */
    protected $refIndexUpdater;

    public function __construct(RefIndexUpdater $refIndexUpdater)
    {
        $this->refIndexUpdater = $refIndexUpdater;
    }

    public function onPublishingOfOneRecordEnded(PublishingOfOneRecordEnded $event): void
    {
        $this->refIndexUpdater->registerRefIndexUpdate($event->getRecord());
    }

    public function onRecursiveRecordPublishingEnded(): void
    {
        $this->refIndexUpdater->writeRefIndexUpdateTask();
    }
}
