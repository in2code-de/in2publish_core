<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SysLogPublisher\EventListener;

use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Features\SysLogPublisher\Domain\Anomaly\SysLogPublisher;

class SysLogPublisherEventListener
{
    /** @var SysLogPublisher */
    protected $sysLogPublisher;

    public function __construct(SysLogPublisher $sysLogPublisher)
    {
        $this->sysLogPublisher = $sysLogPublisher;
    }

    public function onPublishingOfOneRecordEnded(PublishingOfOneRecordEnded $event): void
    {
        $this->sysLogPublisher->publishSysLog($event->getRecord(), $event->getCommonRepository());
    }
}
