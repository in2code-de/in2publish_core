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

use In2code\In2publishCore\Component\FalHandling\FalFinder;
use In2code\In2publishCore\Component\TcaHandling\Publisher\PublisherService;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Model\RecordTree;
use In2code\In2publishCore\Utility\LogUtility;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function array_keys;
use function count;
use function dirname;
use function explode;
use function implode;
use function is_string;
use function json_encode;
use function reset;
use function strlen;
use function strpos;
use function trim;

/**
 * The FileController is responsible for the "Publish Files" Backend module "m2"
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FileController extends AbstractController
{
    protected bool $forcePidInteger = false;
    private ModuleTemplateFactory $moduleTemplateFactory;
    private PageRenderer $pageRenderer;
    private FalFinder $falFinder;
    private FailureCollector $failureCollector;
    private PublisherService $publisherService;

    public function injectPageRenderer(PageRenderer $pageRenderer): void
    {
        $this->pageRenderer = $pageRenderer;
        $this->pageRenderer->addInlineLanguageLabelFile(
            'EXT:in2publish_core/Resources/Private/Language/locallang_m3_js.xlf'
        );
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/In2publishCore/BackendModule');
        $this->pageRenderer->addCssFile(
            'EXT:in2publish_core/Resources/Public/Css/Modules.css',
            'stylesheet',
            'all',
            '',
            false
        );
    }

    public function injectFalFinder(FalFinder $falFinder): void
    {
        $this->falFinder = $falFinder;
    }

    public function injectModuleTemplateFactory(ModuleTemplateFactory $moduleTemplateFactory): void
    {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    public function injectFailureCollector(FailureCollector $failureCollector): void
    {
        $this->failureCollector = $failureCollector;
    }

    public function injectPublisherService(PublisherService $publisherService): void
    {
        $this->publisherService = $publisherService;
    }

    public function indexAction(): ResponseInterface
    {
        $recordTree = $this->tryToGetFolderInstance($this->pid === 0 ? null : $this->pid);

        if (null !== $recordTree) {
            $this->view->assign('recordTree', $recordTree);
        }

        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @param bool $skipNotification Used by the Enterprise Edition. Do not remove despite unused in the CE.
     * @throws StopActionException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) On purpose
     */
    public function publishFolderAction(string $combinedIdentifier, bool $skipNotification = false): void
    {
        $recordTree = $this->tryToGetFolderInstance($combinedIdentifier, true);

        try {
            $this->publisherService->publishRecordTree($recordTree);
            if (!$skipNotification) {
                $this->addFlashMessage(
                    LocalizationUtility::translate('file_publishing.folder', 'in2publish_core', [$combinedIdentifier]),
                    LocalizationUtility::translate('file_publishing.success', 'in2publish_core')
                );
            }
        } catch (Throwable $exception) {
            if (!$skipNotification) {
                $this->addFlashMessage(
                    LocalizationUtility::translate('file_publishing.failure.folder', 'in2publish_core', [$combinedIdentifier]),
                    LocalizationUtility::translate('file_publishing.failure', 'in2publish_core'),
                    AbstractMessage::ERROR
                );
            }
        }

        $this->redirect('index');
    }

    /**
     * @param bool $skipNotification Used by the Enterprise Edition. Do not remove despite unused in the CE.
     * @throws StopActionException
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag) On purpose
     */
    public function publishFileAction(int $uid, string $identifier, int $storage, bool $skipNotification = false): void
    {
        // Special case: The file was moved hence the identifier is a merged one
        if (strpos($identifier, ',')) {
            // Just take the local part of the file identifier.
            // We need the local folder identifier to instantiate the folder record.
            [$identifier] = GeneralUtility::trimExplode(',', $identifier);
        }

        $record = $this->tryToGetFolderInstance($storage . ':' . dirname($identifier));

        if (null !== $record) {
            $relatedRecords = $record->getRelatedRecordByTableAndProperty('sys_file', 'identifier', $identifier);

            $relatedRecord = $this->getRecordToPublish($relatedRecords, $uid);

            try {
                $this->falPublisher->publishFile($relatedRecord);
                if (!$skipNotification) {
                    $this->addFlashMessage(
                        LocalizationUtility::translate('file_publishing.file', 'in2publish_core', [$identifier]),
                        LocalizationUtility::translate('file_publishing.success', 'in2publish_core')
                    );
                }
            } catch (Throwable $e) {
                if (!$skipNotification) {
                    $this->addFlashMessage(
                        LocalizationUtility::translate(
                            'file_publishing.failure.file',
                            'in2publish_core',
                            [$identifier]
                        ),
                        LocalizationUtility::translate('file_publishing.failure', 'in2publish_core'),
                        AbstractMessage::ERROR
                    );
                }
            }
        }

        if (!$skipNotification) {
            $failures = $this->failureCollector->getFailures();
            if (!empty($failures)) {
                $message = '"' . implode('"; "', array_keys($failures)) . '"';
                $title = LocalizationUtility::translate('record_publishing_failure', 'in2publish_core');
                $mostCriticalLogLevel = $this->failureCollector->getMostCriticalLogLevel();
                $severity = LogUtility::translateLogLevelToSeverity($mostCriticalLogLevel);
                $this->addFlashMessage($message, $title, $severity);
            }
        }

        $this->redirect('index');
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

    protected function tryToGetFolderInstance(?string $combinedIdentifier, bool $onlyRoot = false): ?RecordTree
    {
        if (is_string($combinedIdentifier) && strpos($combinedIdentifier, ':') < strlen($combinedIdentifier)) {
            [$storage, $name] = explode(':', $combinedIdentifier);
            $combinedIdentifier = $storage . ':/' . trim($name, '/') . '/';
        }
        return $this->falFinder->findFalRecord($combinedIdentifier, $onlyRoot);
    }

    protected function getRecordToPublish(array $relatedRecords, int $uid): RecordInterface
    {
        $recordsCount = count($relatedRecords);

        if (0 === $recordsCount) {
            throw new RuntimeException('Did not find any record matching the publishing arguments', 1475656572);
        }
        if (1 === $recordsCount) {
            $relatedRecord = reset($relatedRecords);
        } elseif (isset($relatedRecords[$uid])) {
            $relatedRecord = $relatedRecords[$uid];
        } else {
            throw new RuntimeException('Did not find an exact record match for the given arguments', 1475588793);
        }
        return $relatedRecord;
    }
}
