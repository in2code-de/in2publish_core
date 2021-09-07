<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FileEdgeCacheInvalidator\EventListener;

use In2code\In2publishCore\Event\PhysicalFileWasPublished;
use In2code\In2publishCore\Features\FileEdgeCacheInvalidator\Domain\Anomaly\PublishedFileIdentifierCollector;

class FileEdgeCacheInvalidatorEventListener
{
    /** @var PublishedFileIdentifierCollector */
    protected $publishedFileIdentifierCollector;

    public function __construct(PublishedFileIdentifierCollector $publishedFileIdentifierCollector)
    {
        $this->publishedFileIdentifierCollector = $publishedFileIdentifierCollector;
    }

    public function onPhysicalFileWasPublished(PhysicalFileWasPublished $event): void
    {
        $this->publishedFileIdentifierCollector->registerPublishedFile($event->getRecord());
    }

    public function onRecursiveRecordPublishingEnded(): void
    {
        $this->publishedFileIdentifierCollector->writeFlushFileEdgeCacheTask();
    }
}
