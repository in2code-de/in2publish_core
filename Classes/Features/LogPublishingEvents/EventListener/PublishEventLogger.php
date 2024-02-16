<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\LogPublishingEvents\EventListener;

use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\SysLog\Action;
use TYPO3\CMS\Core\SysLog\Error;
use TYPO3\CMS\Core\SysLog\Type;

class PublishEventLogger
{
    public function whenRecursiveRecordPublishingBegan(RecursiveRecordPublishingBegan $event): void
    {
        $backendUser = $this->getBackendUser();
        if (null === $backendUser) {
            return;
        }
        $recordTree = $event->getRecordTree();
        $recordTreeBuildRequest = $recordTree->getRequest();
        if (null === $recordTreeBuildRequest) {
            return;
        }

        $classification = $recordTreeBuildRequest->getTable();
        $id = $recordTreeBuildRequest->getId();
        $rootRecord = $recordTree->getChild($classification, $id);
        if (null === $rootRecord) {
            return;
        }

        $backendUser->writelog(
            Type::EXTENSION,
            Action::UNDEFINED,
            Error::MESSAGE,
            0,
            'Record "{title}" ({classification}:{id}) was published by user {username} ({user})',
            [
                'user' => $backendUser->user['uid'],
                'username' => $backendUser->user[$backendUser->username_column],
                'title' => $rootRecord->__toString(),
                'classification' => $classification,
                'id' => $id,
            ],
            $classification,
            $id,
        );
    }

    protected function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
