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

use In2code\In2publishCore\CommonInjection\ModuleTemplateFactoryInjection;
use In2code\In2publishCore\CommonInjection\PageRendererInjection;
use In2code\In2publishCore\Component\Core\FileHandling\DefaultFalFinderInjection;
use In2code\In2publishCore\Component\Core\FileHandling\Exception\FolderDoesNotExistOnBothSidesException;
use In2code\In2publishCore\Component\Core\Publisher\PublisherServiceInjection;
use In2code\In2publishCore\Component\Core\Publisher\PublishingContext;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTree;
use In2code\In2publishCore\Controller\Traits\CommonViewVariables;
use In2code\In2publishCore\Controller\Traits\ControllerFilterStatus;
use In2code\In2publishCore\Controller\Traits\DeactivateErrorFlashMessage;
use In2code\In2publishCore\Service\Error\FailureCollectorInjection;
use In2code\In2publishCore\Utility\BackendUtility;
use In2code\In2publishCore\Utility\LogUtility;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_keys;
use function http_build_query;
use function implode;
use function json_encode;
use function parse_str;

use const JSON_THROW_ON_ERROR;

/**
 * The FileController is responsible for the "Publish Files" Backend module "m2"
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FileController extends ActionController
{
    use ControllerFilterStatus;
    use DeactivateErrorFlashMessage;
    use CommonViewVariables;
    use PageRendererInjection {
        injectPageRenderer as actualInjectPageRenderer;
    }
    use DefaultFalFinderInjection;
    use ModuleTemplateFactoryInjection;
    use FailureCollectorInjection;
    use PublisherServiceInjection;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->actualInjectPageRenderer($pageRenderer);
        $this->pageRenderer->loadJavaScriptModule('@in2code/in2publish_core/backend-module.js');
        $this->pageRenderer->addCssFile(
            'EXT:in2publish_core/Resources/Public/Css/Modules.css',
            'stylesheet',
            'all',
            '',
            false,
        );
    }

    public function indexAction(): ResponseInterface
    {
        $pid = BackendUtility::getPageIdentifier();
        try {
            $recordTree = $this->defaultFalFinder->findFolderRecord($pid === 0 ? null : $pid);
        } catch (FolderDoesNotExistOnBothSidesException $e) {
            $uri = $this->request->getUri();
            $queryParts = [];
            parse_str($uri->getQuery(), $queryParts);
            $queryParts['id'] = $e->getRootLevelCombinedIdentifier();
            $uri = $uri->withQuery(http_build_query($queryParts));
            return $this->redirectToUri($uri);
        }

        if (null !== $recordTree) {
            $this->view->assign('recordTree', $recordTree);
            $this->view->assign('publishingAvailable', true);
        }

        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/modal.js');
        $this->pageRenderer->addInlineLanguageLabelFile(
            'EXT:in2publish_core/Resources/Private/Language/locallang_m3_js.xlf',
        );
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
        $moduleTemplate->setContent($this->view->render());
        /** @see packages/in2publish_core/Resources/Private/Templates/File/Index.html */
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @param bool $skipNotification Used by the Enterprise Edition. Do not remove despite unused in the CE.
     *
     * @throws FolderDoesNotExistOnBothSidesException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) On purpose
     */
    public function publishFolderAction(string $combinedIdentifier, bool $skipNotification = false): ResponseInterface
    {
        $recordTree = $this->defaultFalFinder->findFolderRecord($combinedIdentifier, true);
        $publishingContext = new PublishingContext($recordTree);

        try {
            $this->publisherService->publish($publishingContext);
            if (!$skipNotification) {
                $this->addFlashMessage(
                    LocalizationUtility::translate('file_publishing.folder', 'In2publishCore', [$combinedIdentifier]),
                    LocalizationUtility::translate('file_publishing.success', 'In2publishCore'),
                );
            }
        } catch (Throwable $exception) {
            if (!$skipNotification) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'file_publishing.failure.folder',
                        'In2publishCore',
                        [$combinedIdentifier],
                    ),
                    LocalizationUtility::translate('file_publishing.failure', 'In2publishCore'),
                    ContextualFeedbackSeverity::ERROR,
                );
            }
        }

        return $this->redirect('index');
    }

    /**
     * @param bool $skipNotification Used by the Enterprise Edition. Do not remove despite unused in the CE.
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) On purpose
     */
    public function publishFileAction(string $combinedIdentifier, bool $skipNotification = false): ResponseInterface
    {
        $recordTree = $this->defaultFalFinder->findFileRecord($combinedIdentifier);
        $publishingContext = new PublishingContext($recordTree);

        try {
            $this->publisherService->publish($publishingContext);
            if (!$skipNotification) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'file_publishing.file',
                        'In2publishCore',
                        [$combinedIdentifier],
                    ),
                    LocalizationUtility::translate('file_publishing.success', 'In2publishCore'),
                );
            }
        } catch (Throwable $e) {
            if (!$skipNotification) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'file_publishing.failure.file',
                        'In2publishCore',
                        [$combinedIdentifier],
                    )
                    . $e->getMessage(),
                    LocalizationUtility::translate('file_publishing.failure', 'In2publishCore'),
                    ContextualFeedbackSeverity::ERROR,
                );
            }
        }

        if (!$skipNotification) {
            $failures = $this->failureCollector->getFailures();
            if (!empty($failures)) {
                $message = '"' . implode('"; "', array_keys($failures)) . '"';
                $title = LocalizationUtility::translate('record_publishing_failure', 'In2publishCore');
                $mostCriticalLogLevel = $this->failureCollector->getMostCriticalLogLevel();
                $severity = LogUtility::translateLogLevelToSeverity($mostCriticalLogLevel);
                $this->addFlashMessage($message, $title, $severity);
            }
        }

        return $this->redirect('index');
    }

    /**
     * toggle filter status and save the filter status in the current backendUser's session.
     *
     * @param string $filter "changed", "added", "deleted"
     */
    public function toggleFilterStatusAction(string $filter): ResponseInterface
    {
        $return = $this->toggleFilterStatus('in2publish_filter_files_', $filter);
        return $this->jsonResponse(json_encode($return, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws FolderDoesNotExistOnBothSidesException
     */
    protected function tryToGetFolderInstance(?string $combinedIdentifier, bool $onlyRoot = false): ?RecordTree
    {
        return $this->defaultFalFinder->findFolderRecord($combinedIdentifier, $onlyRoot);
    }
}
