<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\ContextMenuPublishEntry\Controller;

use In2code\In2publishCore\Command\PublishTaskRunner\RunTasksInQueueCommand;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Service\Permission\PermissionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function json_encode;
use function sprintf;

class PublishPageAjaxController
{
    public function publishPage(ServerRequestInterface $request): ResponseInterface
    {
        $response = GeneralUtility::makeInstance(Response::class);

        $page = $request->getQueryParams()['page'] ?? null;

        $content = [
            'success' => false,
            'message' => 'Unknown error',
            'error' => true,
        ];

        if (!GeneralUtility::makeInstance(PermissionService::class)->isUserAllowedToPublish()) {
            $content['message'] = 'You are not allowed to publish this page';
            $content['error'] = false;
        }

        if (null === $page) {
            $content['message'] = 'No page parameter was transferred';
        } else {
            try {
                $commonRepository = CommonRepository::getDefaultInstance();

                $record = $commonRepository->findByIdentifier($page, 'pages');

                if ($record->isPublishable()) {
                    $commonRepository->publishRecordRecursive($record);
                    $dispatcher = GeneralUtility::makeInstance(RemoteCommandDispatcher::class);
                    $request = GeneralUtility::makeInstance(
                        RemoteCommandRequest::class,
                        RunTasksInQueueCommand::IDENTIFIER
                    );
                    $rceResponse = $dispatcher->dispatch($request);
                    if ($rceResponse->isSuccessful()) {
                        $content['success'] = true;
                        $content['error'] = false;
                        $content['message'] =
                            sprintf(
                                'Page "%s" successfully published',
                                BackendUtility::getRecordTitle('pages', $record->getLocalProperties())
                            );
                    } else {
                        $content['message'] =
                            sprintf(
                                'Error during publishing of page "%s". Please check your logs.',
                                BackendUtility::getRecordTitle('pages', $record->getLocalProperties())
                            );
                    }
                } else {
                    $content['error'] = false;
                    $content['message'] = 'This record is not yet publishable';
                }
            } catch (Throwable $exception) {
                $content['message'] = (string)$exception;
            }
        }

        $response->getBody()->write(json_encode($content));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
