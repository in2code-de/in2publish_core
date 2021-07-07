<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\PhysicalFilePublisher\EventListener;

use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Features\PhysicalFilePublisher\Domain\Anomaly\PhysicalFilePublisher;

class PhysicalFilePublisherEventListener
{
    /** @var PhysicalFilePublisher */
    protected $physicalFilePublisher;

    public function __construct(PhysicalFilePublisher $physicalFilePublisher)
    {
        $this->physicalFilePublisher = $physicalFilePublisher;
    }

    public function onPublishingOfOneRecordEnded(PublishingOfOneRecordEnded $event): void
    {
        $this->physicalFilePublisher->publishPhysicalFileOfSysFile($event->getRecord());
    }
}
