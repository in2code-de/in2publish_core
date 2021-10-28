<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\RedirectsSupport\Domain\Model;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Service\Configuration\TcaService;
use In2code\In2publishCore\Service\Database\RawRecordService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class SysRedirect extends AbstractEntity
{
    /** @var string */
    protected $sourceHost;

    /** @var string */
    protected $sourcePath;

    /** @var string */
    protected $target;

    /** @var int */
    protected $pageUid;

    /** @var string */
    protected $siteId;

    /** @var bool */
    protected $deleted;

    /** @var array */
    protected $rtc = [];

    /**
     * @return string
     */
    public function getSourceHost(): string
    {
        return $this->sourceHost;
    }

    /**
     * @param string $sourceHost
     */
    public function setSourceHost(string $sourceHost): void
    {
        $this->sourceHost = $sourceHost;
    }

    /**
     * @return string
     */
    public function getSourcePath(): string
    {
        return $this->sourcePath;
    }

    /**
     * @param string $sourcePath
     */
    public function setSourcePath(string $sourcePath): void
    {
        $this->sourcePath = $sourcePath;
    }

    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * @param string $target
     */
    public function setTarget(string $target): void
    {
        $this->target = $target;
    }

    /**
     * @return null|int
     */
    public function getPageUid(): ?int
    {
        return $this->pageUid;
    }

    /**
     * @param null|int $pageUid
     */
    public function setPageUid(?int $pageUid): void
    {
        $this->pageUid = $pageUid;
    }

    /**
     * @return null|string
     */
    public function getSiteId(): ?string
    {
        return $this->siteId;
    }

    /**
     * @param string $siteId
     */
    public function setSiteId(string $siteId): void
    {
        $this->siteId = $siteId;
    }

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }

    public function __toString()
    {
        return sprintf('Redirect [%d] (%s) %s -> %s', $this->uid, $this->sourceHost, $this->sourcePath, $this->target);
    }

    public function hasPublishedAssociatedPage(): bool
    {
        if (null === $this->pageUid) {
            return false;
        }
        $rawRecordService = GeneralUtility::makeInstance(RawRecordService::class);
        $record = $rawRecordService->getRawRecord('pages', $this->pageUid, 'foreign');
        return $record !== null;
    }

    public function getPageTitle(): ?string
    {
        if (null === $this->pageUid) {
            return null;
        }
        $rawRecordService = GeneralUtility::makeInstance(RawRecordService::class);
        $record = $rawRecordService->getRawRecord('pages', $this->pageUid, 'local');
        return GeneralUtility::makeInstance(TcaService::class)->getRecordLabel($record, 'pages');
    }

    public function getRecord(): RecordInterface
    {
        if (!isset($this->rtc['record'])) {
            $this->rtc['record'] = CommonRepository::getDefaultInstance()->findByIdentifier($this->uid, 'sys_redirect');
        }
        return $this->rtc['record'];
    }

    public function getPublishingState(): string
    {
        $record = $this->getRecord();
        if (!$record->isChanged()) {
            // Nothing to publish
            return 'unchanged';
        }
        if ('*' === $this->sourceHost) {
            // A wildcard host does not require any site or page association
            return 'publishable';
        }
        if (null !== $this->siteId) {
            // Sites are preferred. If a site is given, use it.
            return 'publishable';
        }
        if (null === $this->pageUid && null === $this->siteId) {
            // Can not publish without site or page
            return 'siteRequired';
        }
        if (!$this->hasPublishedAssociatedPage()) {
            // If a page is not published, there is no site and therefore no domain
            return 'requiresPagePublishing';
        }
        return 'publishable';
    }
}
