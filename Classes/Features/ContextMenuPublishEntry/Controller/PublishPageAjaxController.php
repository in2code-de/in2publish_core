<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\ContextMenuPublishEntry\Controller;

use In2code\In2publishCore\Command\PublishTaskRunner\RunTasksInQueueCommand;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandDispatcher;
use In2code\In2publishCore\Communication\RemoteCommandExecution\RemoteCommandRequest;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function json_encode;

class PublishPageAjaxController
{
    public function publishPage(ServerRequestInterface $request): ResponseInterface
    {
        $response = GeneralUtility::makeInstance(Response::class);

        $page = $request->getQueryParams()['page'] ?? null;

        $content = [
            'success' => false,
            'message' => 'Unknown error',
        ];

        if (null === $page) {
            $content['message'] = 'No page parameter was transferred';
        } else {
            try {
                $commonRepository = CommonRepository::getDefaultInstance();
                $record = $commonRepository->findByIdentifier($page, 'pages');
//                $commonRepository->publishRecordRecursive($record);

                $dispatcher = GeneralUtility::makeInstance(RemoteCommandDispatcher::class);
                $request =
                    GeneralUtility::makeInstance(RemoteCommandRequest::class, RunTasksInQueueCommand::IDENTIFIER);
                $rceResponse = $dispatcher->dispatch($request);
                if ($rceResponse->isSuccessful()) {
                    $content['success'] = true;
                    $content['message'] = 'Page '
                                          . BackendUtility::getRecordTitle('pages', $record->getLocalProperties())
                                          . ' successfully published';
                } else {
                    $content['message'] = 'Error during publishing of page '
                                          . BackendUtility::getRecordTitle('pages', $record->getLocalProperties())
                                          . '. Please check your logs.';
                }
            } catch (Throwable $exception) {
                $content['message'] = (string)$exception;
            }
        }

        $response->getBody()->write(json_encode($content));

        return $response;
    }
}
