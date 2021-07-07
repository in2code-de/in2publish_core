<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\EventListener;

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord;
use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Features\RedirectsSupport\DataBender\RedirectSourceHostReplacement;
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

    public function __construct(
        ConfigContainer $configContainer,
        PageRecordRedirectEnhancer $pageRecordRedirectEnhancer,
        RedirectSourceHostReplacement $redirectSourceHostReplacement
    ) {
        $this->enabled = (
            $configContainer->get('features.redirectsSupport.enable')
            && ExtensionManagementUtility::isLoaded('redirects')
        );
        $this->pageRecordRedirectEnhancer = $pageRecordRedirectEnhancer;
        $this->redirectSourceHostReplacement = $redirectSourceHostReplacement;
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
}
