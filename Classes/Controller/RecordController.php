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

use In2code\In2publishCore\CommonInjection\PageRendererInjection;
use In2code\In2publishCore\Component\Core\Publisher\PublisherServiceInjection;
use In2code\In2publishCore\Component\Core\Publisher\PublishingContext;
use In2code\In2publishCore\Component\Core\RecordIndexInjection;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuilderInjection;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuildRequest;
use In2code\In2publishCore\Controller\Traits\CommonViewVariables;
use In2code\In2publishCore\Controller\Traits\ControllerFilterStatus;
use In2code\In2publishCore\Controller\Traits\ControllerModuleTemplate;
use In2code\In2publishCore\Controller\Traits\DeactivateErrorFlashMessage;
use In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\Exception\StopwatchWasNotStartedException;
use In2code\In2publishCore\Features\MetricsAndDebug\Stopwatch\SimpleStopwatchInjection;
use In2code\In2publishCore\In2publishCoreException;
use In2code\In2publishCore\Service\Error\FailureCollectorInjection;
use In2code\In2publishCore\Service\Permission\PermissionServiceInjection;
use In2code\In2publishCore\Utility\BackendUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use In2code\In2publishCore\Utility\LogUtility;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_keys;
use function implode;
use function is_int;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * Content publishing Controller. Any action is for the "Publish Records" Backend module "m1"
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RecordController extends ActionController
{
    use ControllerFilterStatus;
    use ControllerModuleTemplate;
    use DeactivateErrorFlashMessage;
    use CommonViewVariables;
    use RecordIndexInjection;
    use RecordTreeBuilderInjection;
    use PageRendererInjection {
        injectPageRenderer as actualInjectPageRenderer;
    }
    use FailureCollectorInjection;
    use PublisherServiceInjection;
    use PermissionServiceInjection;
    use SimpleStopwatchInjection;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->actualInjectPageRenderer($pageRenderer);

        $this->pageRenderer->loadJavaScriptModule('@in2code/in2publish_core/backend-module.js');
        $this->pageRenderer->loadJavaScriptModule('@in2code/in2publish_core/backend-enhancements.js');

        $this->pageRenderer->addCssFile(
            'EXT:in2publish_core/Resources/Public/Css/Modules.css',
            'stylesheet',
            'all',
            '',
            false,
        );
    }

    public function initializeIndexAction(): void
    {
        $backendUser = $this->getBackendUser();
        $data = $backendUser->getModuleData('tx_in2publishcore_m1') ?? ['pageRecursionLimit' => 1];
        if ($this->request->hasArgument('pageRecursionLimit')) {
            $pageRecursionLimit = (int)$this->request->getArgument('pageRecursionLimit');
            $data['pageRecursionLimit'] = $pageRecursionLimit;
            $backendUser->pushModuleData('tx_in2publishcore_m1', $data);
        } else {
            $this->request = $this->request->withArgument('pageRecursionLimit', $data['pageRecursionLimit'] ?? 1);
        }

        $this->moduleTemplate->setModuleClass('in2publish_core_m1');

        $menuRegistry = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry();
        $menu = $menuRegistry->makeMenu();
        $menu->setIdentifier('depth');
        $menu->setLabel(LocalizationUtility::translate('m1.page_recursion', 'In2publishCore'));
        for ($i = 0; $i <= 10; $i++) {
            $menuItem = $menu->makeMenuItem();
            $menuItem->setActive($i === $data['pageRecursionLimit']);
            if ($i > 1) {
                $title = LocalizationUtility::translate('m1.page_recursion.depths', 'In2publishCore', [$i]);
            } else {
                $title = LocalizationUtility::translate('m1.page_recursion.depth', 'In2publishCore', [$i]);
            }
            $menuItem->setTitle($title);
            $menuItem->setHref($this->uriBuilder->uriFor('index', ['pageRecursionLimit' => $i]));
            $menu->addMenuItem($menuItem);
        }
        $menuRegistry->addMenu($menu);
    }

    /**
     * Create a Record instance of the current selected page
     * If none is chosen, a Record with uid = 0 is created which
     * represents the instance root
     */
    public function indexAction(int $pageRecursionLimit): ResponseInterface
    {
        $pid = BackendUtility::getPageIdentifier();
        if (!is_int($pid)) {
            $pid = 0;
        }
        $request = new RecordTreeBuildRequest('pages', $pid, $pageRecursionLimit);
        $recordTree = $this->recordTreeBuilder->buildRecordTree($request);

        $localDbAvailable = null !== DatabaseUtility::buildLocalDatabaseConnection();
        try {
            $foreignDbAvailable = null !== DatabaseUtility::buildForeignDatabaseConnection();
        } catch (Throwable $exception) {
            $foreignDbAvailable = false;
        }

        $this->moduleTemplate->assignMultiple([
            'recordTree' => $recordTree,
            'localDatabaseConnectionAvailable' => $localDbAvailable,
            'foreignDatabaseConnectionAvailable' => $foreignDbAvailable,
            'publishingAvailable' => $localDbAvailable && $foreignDbAvailable,
        ]);
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

    public function publishRecordAction(int $recordId): ResponseInterface
    {
        $request = new RecordTreeBuildRequest('pages', $recordId, 0);
        $recordTree = $this->recordTreeBuilder->buildRecordTree($request);

        $actualRecord = $recordTree->getChild('pages', $recordId);
        if (null === $actualRecord) {
            return $this->addFlashMessagesAndRedirectToIndex();
        }
        $subRecordTree = new RecordTree([$actualRecord], $request);
        $publishingContext = new PublishingContext($subRecordTree);
        $this->publisherService->publish($publishingContext);

        return $this->addFlashMessagesAndRedirectToIndex();
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

    /**
     * Add success message and redirect to indexAction
     */
    protected function addFlashMessagesAndRedirectToIndex(): ResponseInterface
    {
        $failures = $this->failureCollector->getFailures();

        try {
            $executionTime = $this->simpleStopwatch->getTime();
        } catch (StopwatchWasNotStartedException $e) {
            $executionTime = 'Timer was never started';
        }
        if (empty($failures)) {
            $message = '';
            $title = LocalizationUtility::translate('record_published', 'In2publishCore', [$executionTime]);
            $severity = ContextualFeedbackSeverity::OK;
        } else {
            $message = '"' . implode('"; "', array_keys($failures)) . '"';
            $title = LocalizationUtility::translate('record_publishing_failure', 'In2publishCore', [$executionTime]);
            $mostCriticalLogLevel = $this->failureCollector->getMostCriticalLogLevel();
            $severity = LogUtility::translateLogLevelToSeverity($mostCriticalLogLevel);
        }
        $this->addFlashMessage($message, $title, $severity);

        $arguments = [];
        $queryParams = $this->request->getQueryParams();
        if (isset($queryParams['id'])) {
            $arguments['id'] = (int)$queryParams['id'];
        }

        return $this->redirect('index', 'Record', null, $arguments);
    }

    public function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
