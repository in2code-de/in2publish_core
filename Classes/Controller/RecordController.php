<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Controller;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
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

use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Component\TcaHandling\RecordTreeBuilder;
use In2code\In2publishCore\Component\TcaHandling\Publisher\PublisherService;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Controller\Traits\ControllerModuleTemplate;
use In2code\In2publishCore\Domain\Service\ExecutionTimeService;
use In2code\In2publishCore\Event\RecordWasCreatedForDetailAction;
use In2code\In2publishCore\Event\RecordWasSelectedForPublishing;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Environment\EnvironmentService;
use In2code\In2publishCore\Service\Error\FailureCollector;
use In2code\In2publishCore\Service\Permission\PermissionService;
use In2code\In2publishCore\Utility\LogUtility;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_keys;
use function array_merge;
use function implode;
use function json_encode;

/**
 * Content publishing Controller. Any action is for the "Publish Records" Backend module "m1"
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RecordController extends AbstractController
{
    use ControllerModuleTemplate;

    protected FailureCollector $failureCollector;
    protected PermissionService $permissionService;
    protected RecordTreeBuilder $recordTreeBuilder;
    protected PublisherService $publisherService;

    public function __construct(
        ConfigContainer $configContainer,
        ExecutionTimeService $executionTimeService,
        EnvironmentService $environmentService,
        RemoteCommandDispatcher $remoteCommandDispatcher,
        FailureCollector $failureCollector,
        PermissionService $permissionService,
        PageRenderer $pageRenderer
    ) {
        parent::__construct(
            $configContainer,
            $executionTimeService,
            $environmentService,
            $remoteCommandDispatcher
        );
        $this->failureCollector = $failureCollector;
        $this->permissionService = $permissionService;
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/In2publishCore/BackendModule');
        $pageRenderer->addCssFile(
            'EXT:in2publish_core/Resources/Public/Css/Modules.css',
            'stylesheet',
            'all',
            '',
            false
        );
    }

    public function injectRecordTreeBuilder(RecordTreeBuilder $recordTreeBuilder): void
    {
        $this->recordTreeBuilder = $recordTreeBuilder;
    }

    /**
     * Create a Record instance of the current selected page
     * If none is chosen, a Record with uid = 0 is created which
     * represents the instance root
     */
    public function indexAction(): ResponseInterface
    {
        $recordTree = $this->recordTreeBuilder->buildRecordTree('pages', $this->pid);

        $this->view->assign('recordTree', $recordTree);
        return $this->htmlResponse();
    }

    /**
     * Show record details (difference view) to a page
     * Normally called via AJAX
     *
     * @param int $identifier record identifier
     */
    public function detailAction(int $identifier, string $tableName): ResponseInterface
    {
        $record = $this->recordFinder->findRecordByUidForPublishing($identifier, $tableName);

        $this->eventDispatcher->dispatch(new RecordWasCreatedForDetailAction($this, $record));

        $this->view->assign('record', $record);
        return $this->htmlResponse();
    }

    /**
     * Check if user is allowed to publish
     *
     * @throws In2publishCoreException
     */
    public function initializePublishRecordAction(): void
    {
        if (!$this->permissionService->isUserAllowedToPublish()) {
            throw new In2publishCoreException('You are not allowed to publish', 1435306780);
        }
    }

    /**
     * Publish the selected page record with all related content records
     *
     * @param int $identifier
     * @param string|null $returnUrl
     *
     * @throws StopActionException
     */
    public function publishRecordAction(int $identifier, string $returnUrl = null): void
    {
        $recordTree = $this->recordTreeBuilder->buildRecordTree('pages', $identifier);
        $this->publisherService->publishRecordTree($recordTree);
        $this->redirect('index');
    }

    /**
     * toggle filter status and save the filter status
     * in the current backendUser's session.
     *
     * @param string $filter "changed", "added", "deleted"
     */
    public function toggleFilterStatusAction(string $filter): ResponseInterface
    {
        $return = $this->toggleFilterStatus('in2publish_filter_records_', $filter);
        return $this->jsonResponse(json_encode($return, JSON_THROW_ON_ERROR));
    }

    protected function publishRecord(int $identifier, array $exceptTableNames = []): void
    {
        $record = $this->recordFinder->findRecordByUidForPublishing($identifier, 'pages');

        $this->eventDispatcher->dispatch(new RecordWasSelectedForPublishing($record, $this));

        try {
            $this->recordPublisher->publishRecordRecursive(
                $record,
                array_merge($this->configContainer->get('excludeRelatedTables'), $exceptTableNames)
            );
        } catch (Throwable $exception) {
            $this->logger->error('Error while publishing', ['exception' => $exception]);
            $this->addFlashMessage($exception->getMessage(), '', AbstractMessage::ERROR);
        }
        $this->runTasks();
    }

    /**
     * Add success message and redirect to indexAction
     *
     * @throws StopActionException
     */
    protected function addFlashMessagesAndRedirectToIndex(): void
    {
        $failures = $this->failureCollector->getFailures();

        if (empty($failures)) {
            $message = '';
            $title = LocalizationUtility::translate('record_published', 'in2publish_core');
            $severity = AbstractMessage::OK;
        } else {
            $message = '"' . implode('"; "', array_keys($failures)) . '"';
            $title = LocalizationUtility::translate('record_publishing_failure', 'in2publish_core');
            $mostCriticalLogLevel = $this->failureCollector->getMostCriticalLogLevel();
            $severity = LogUtility::translateLogLevelToSeverity($mostCriticalLogLevel);
        }
        $this->addFlashMessage($message, $title, $severity);

        $this->redirect('index', 'Record');
    }

    public function injectPublisherService(PublisherService $publisherService): void
    {
        $this->publisherService = $publisherService;
    }
}
