<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\NewsSupport\EventListener;

use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use In2code\In2publishCore\Features\NewsSupport\Domain\Anomaly\NewsCacheInvalidator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class NewsSupportEventListener
{
    /** @var bool */
    protected $enabled;

    /** @var NewsCacheInvalidator */
    protected $newsCacheInvalidator;

    public function __construct(NewsCacheInvalidator $newsCacheInvalidator)
    {
        $this->enabled = ExtensionManagementUtility::isLoaded('news');
        $this->newsCacheInvalidator = $newsCacheInvalidator;
    }

    public function onPublishingOfOneRecordBegan(PublishingOfOneRecordBegan $event): void
    {
        if (!$this->enabled) {
            return;
        }
        $this->newsCacheInvalidator->registerClearCacheTasks($event->getRecord());
    }

    public function onRecursiveRecordPublishingEnded(RecursiveRecordPublishingEnded $event): void
    {
        if (!$this->enabled) {
            return;
        }
        $this->newsCacheInvalidator->writeClearCacheTask();
    }
}
