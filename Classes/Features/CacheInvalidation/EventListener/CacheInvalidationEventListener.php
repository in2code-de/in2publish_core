<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\CacheInvalidation\EventListener;

use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Features\CacheInvalidation\Domain\Anomaly\CacheInvalidator;

class CacheInvalidationEventListener
{
    /** @var CacheInvalidator */
    protected $cacheInvalidator;

    public function __construct(CacheInvalidator $cacheInvalidator)
    {
        $this->cacheInvalidator = $cacheInvalidator;
    }

    public function onPublishingOfOneRecordBegan(PublishingOfOneRecordBegan $event): void
    {
        $this->cacheInvalidator->registerClearCacheTasks($event->getRecord());
    }

    public function onRecursiveRecordPublishingEnded(): void
    {
        $this->cacheInvalidator->writeClearCacheTask();
    }
}
