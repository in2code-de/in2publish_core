<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Domain\Model;

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Event\DetermineIfRecordIsPublishing;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Service\Database\RawRecordService;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function sprintf;

class SysRedirectDatabaseRecord extends DatabaseRecord
{
    public function hasPublishedAssociatedPage(): bool
    {
        $pageUid = $this->getLocalProps()['tx_in2publishcore_page_uid'] ?? null;
        if (null === $pageUid) {
            return false;
        }
        $rawRecordService = GeneralUtility::makeInstance(RawRecordService::class);
        $record = $rawRecordService->getRawRecord('pages', $pageUid, 'foreign');
        return $record !== null;
    }

    public function getPageTitle(): ?string
    {
        $pageUid = $this->getLocalProps()['tx_in2publishcore_page_uid'] ?? null;
        if (null === $pageUid) {
            return null;
        }
        $rawRecordService = GeneralUtility::makeInstance(RawRecordService::class);
        $record = $rawRecordService->getRawRecord('pages', $pageUid, 'local');
        if (null === $record) {
            return null;
        }
        return GeneralUtility::makeInstance(TcaService::class)->getRecordLabel($record, 'pages');
    }

    public function getPublishingState(): string
    {
        $event = new DetermineIfRecordIsPublishing('sys_redirect', $this->getId());
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
        $eventDispatcher->dispatch($event);
        if ($event->isPublishing()) {
            return 'publishing';
        }
        if (!$this->isChanged()) {
            // Nothing to publish
            return 'unchanged';
        }
        $localProps = $this->getLocalProps();
        if ('*' === ($localProps['sourceHost'] ?? null)) {
            // A wildcard host does not require any site or page association
            return 'publishable';
        }
        if (null !== ($localProps['tx_in2publishcore_foreign_site_id'] ?? null)) {
            // Sites are preferred. If a site is given, use it.
            return 'publishable';
        }
        if (null === ($localProps['tx_in2publishcore_page_uid'] ?? null)) {
            // Can not publish without site or page
            return 'siteRequired';
        }
        if (!$this->hasPublishedAssociatedPage()) {
            // If a page is not published, there is no site and therefore no domain
            return 'requiresPagePublishing';
        }
        return 'publishable';
    }

    protected function calculateChangedProps(): array
    {
        $props = parent::calculateChangedProps();
        if (empty($props)) {
            // (Sanity) check the published domain. If the redirect is associated
            // with a site or page, the local and foreign source_host must differ
            if (
                $this->localProps['source_host'] === $this->foreignProps['source_host']
                && '*' !== ($this->localProps['sourceHost'] ?? null)
                && (
                    null !== ($this->localProps['tx_in2publishcore_foreign_site_id'] ?? null)
                    || null !== ($this->localProps['tx_in2publishcore_page_uid'] ?? null)
                )
            ) {
                // source_host is ignored by default, but in case it is not what it should be,
                // mark the record as changed to allow users to publish the incorrect redirect
                $props[] = 'source_host';
            }
        }
        return $props;
    }

    public function __toString(): string
    {
        $localProps = $this->getLocalProps();
        return sprintf(
            'Redirect [%d] (%s) %s -> %s',
            $this->getId(),
            $localProps['sourceHost'] ?? '',
            $localProps['sourcePath'] ?? '',
            $localProps['target'] ?? '',
        );
    }
}
