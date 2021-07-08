<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\EventListener;

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord;
use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use In2code\In2publishCore\Features\RedirectsSupport\DataBender\RedirectSourceHostReplacement;
use In2code\In2publishCore\Features\RedirectsSupport\Domain\Anomaly\RedirectCacheUpdater;
use In2code\In2publishCore\Features\RedirectsSupport\PageRecordRedirectEnhancer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class RedirectsSupportEventListener
{
    /** @var bool */
    protected $enabled;

    /** @var PageRecordRedirectEnhancer */
    protected $pageRecordRedirectEnhancer;

    /** @var RedirectSourceHostReplacement */
    protected $redirectSourceHostReplacement;

    /** @var RedirectCacheUpdater */
    protected $redirectCacheUpdater;

    public function __construct(
        ConfigContainer $configContainer,
        PageRecordRedirectEnhancer $pageRecordRedirectEnhancer,
        RedirectSourceHostReplacement $redirectSourceHostReplacement,
        RedirectCacheUpdater $redirectCacheUpdater
    ) {
        $this->enabled = (
            $configContainer->get('features.redirectsSupport.enable')
            && ExtensionManagementUtility::isLoaded('redirects')
        );
        $this->pageRecordRedirectEnhancer = $pageRecordRedirectEnhancer;
        $this->redirectSourceHostReplacement = $redirectSourceHostReplacement;
        $this->redirectCacheUpdater = $redirectCacheUpdater;
    }

    public function onAllRelatedRecordsWereAddedToOneRecord(AllRelatedRecordsWereAddedToOneRecord $event): void
    {
        if (!$this->enabled) {
            return;
        }
        $this->pageRecordRedirectEnhancer->addRedirectsToPageRecord($event->getRecord());
    }

    public function onPublishingOfOneRecordBegan(PublishingOfOneRecordBegan $event): void
    {
        if (!$this->enabled) {
            return;
        }
        $this->redirectSourceHostReplacement->replaceLocalWithForeignSourceHost($event->getRecord());
    }

    public function onPublishingOfOneRecordEnded(PublishingOfOneRecordEnded $event): void
    {
        if (!$this->enabled) {
            return;
        }
        $this->redirectCacheUpdater->publishRecordRecursiveAfterPublishing($event->getRecord());
    }

    public function onRecursiveRecordPublishingEnded(RecursiveRecordPublishingEnded $event): void
    {
        if (!$this->enabled) {
            return;
        }
        $this->redirectCacheUpdater->publishRecordRecursiveEnd();
    }
}
