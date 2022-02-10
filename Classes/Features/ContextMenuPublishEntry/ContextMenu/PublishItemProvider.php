<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\ContextMenuPublishEntry\ContextMenu;

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

use In2code\In2publishCore\Event\VoteIfRecordIsPublishable;
use In2code\In2publishCore\Service\Permission\PermissionService;
use TYPO3\CMS\Backend\ContextMenu\ItemProviders\AbstractProvider;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PublishItemProvider extends AbstractProvider
{
    protected $itemsConfiguration = [
        'publish' => [
            'label' => 'LLL:EXT:in2publish_core/Resources/Private/Language/Features/ContextMenuPublishEntry.xlf:publish_page',
            'iconIdentifier' => 'tx_in2publishcore_contextmenupublishentry_publish',
            'callbackAction' => 'publishRecord',
        ],
    ];

    protected PermissionService $permissionService;

    public function __construct(string $table, string $identifier, string $context = '')
    {
        parent::__construct($table, $identifier, $context);
        $this->permissionService = GeneralUtility::makeInstance(PermissionService::class);
    }

    public function canHandle(): bool
    {
        return $this->table === 'pages';
    }

    public function getPriority(): int
    {
        return 43;
    }

    public function addItems(array $items): array
    {
        if ($this->permissionService->isUserAllowedToPublish()) {
            $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
            $event = new VoteIfRecordIsPublishable($this->table, (int)$this->identifier);
            $eventDispatcher->dispatch($event);
            if ($event->getVotingResult()) {
                return parent::addItems($items);
            }
        }
        return $items;
    }

    /** @throws RouteNotFoundException */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $attributes = [];
        if ($itemName === 'publish') {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $publishUrl = (string)$uriBuilder->buildUriFromRoute(
                'ajax_in2publishcore_contextmenupublishentry_publish',
                ['id' => $this->identifier]
            );
            $attributes += [
                'data-publish-url' => $publishUrl,
                'data-callback-module' => 'TYPO3/CMS/In2publishCore/ContextMenuPublishEntry',
            ];
        }
        return $attributes;
    }
}
