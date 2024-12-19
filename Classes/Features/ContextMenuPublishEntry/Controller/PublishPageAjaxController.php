<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\ContextMenuPublishEntry\Controller;

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

use In2code\In2publishCore\Component\Core\Publisher\PublisherServiceInjection;
use In2code\In2publishCore\Component\Core\Publisher\PublishingContext;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuilderInjection;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuildRequest;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Service\Exception\TaskExecutionFailedException;
use In2code\In2publishCore\Service\Permission\PermissionServiceInjection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function json_encode;

use const JSON_THROW_ON_ERROR;

class PublishPageAjaxController
{
    use RecordTreeBuilderInjection;
    use PublisherServiceInjection;
    use PermissionServiceInjection;

    public function publishPage(ServerRequestInterface $request): ResponseInterface
    {
        $response = new Response();

        $page = $request->getQueryParams()['id'] ?? null;

        $content = [
            'success' => false,
            'label' => 'Unknown error',
            'lArgs' => [],
            'error' => true,
        ];

        if (!$this->permissionService->isUserAllowedToPublish()) {
            $content['label'] = 'context_menu_publish_entry.forbidden';
            $content['error'] = false;
        }

        if (null === $page) {
            $content['label'] = 'context_menu_publish_entry.missing_page';
        } else {
            try {
                // TODO: reimplement isPublishable method in Record
                $recordTreeBuildRequest = new RecordTreeBuildRequest('pages', (int)$page, 0);
                $recordTree = $this->recordTreeBuilder->buildRecordTree($recordTreeBuildRequest);
                $record = $recordTree->getChild('pages', (int)$page, 0);
                $publishingContext = new PublishingContext($recordTree);
                if (null !== $record && $record->isPublishable()) {
                    try {
                        $this->publisherService->publish($publishingContext);
                        $content['success'] = true;
                        $content['error'] = false;
                        $content['label'] = 'context_menu_publish_entry.page_published';
                    } catch (TaskExecutionFailedException $exception) {
                        $content['label'] = 'context_menu_publish_entry.publishing_error';
                    }
                    $record = $recordTree->getChild('pages', (int)$page);
                    $content['lArgs'][] = BackendUtility::getRecordTitle('pages', $record->getLocalProps());
                } else {
                    $content['error'] = false;
                    $content['label'] = 'context_menu_publish_entry.not_publishable';
                }
            } catch (Throwable $exception) {
                $content['label'] = (string)$exception;
            }
        }

        $lArgs = !empty($content['lArgs']) ? $content['lArgs'] : null;
        $content['message'] = LocalizationUtility::translate($content['label'], 'In2publishCore', $lArgs);
        $response->getBody()->write(json_encode($content, JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
