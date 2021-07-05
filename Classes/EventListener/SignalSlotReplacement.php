<?php

declare(strict_types=1);

namespace In2code\In2publishCore\EventListener;

use In2code\In2publishCore\Controller\FileController;
use In2code\In2publishCore\Controller\RecordController;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Event\FolderInstanceWasCreated;
use In2code\In2publishCore\Event\RecordWasCreatedForDetailAction;
use In2code\In2publishCore\Event\VoteIfRecordShouldBeIgnored;
use In2code\In2publishCore\Event\VoteIfRecordShouldBeSkipped;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

class SignalSlotReplacement
{
    /** @var Dispatcher */
    protected $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function onFolderInstanceWasCreated(FolderInstanceWasCreated $event): void
    {
        try {
            $this->dispatcher->dispatch(
                FileController::class,
                'folderInstanceCreated',
                [$event->getRecord()]
            );
        } catch (InvalidSlotException | InvalidSlotReturnException $e) {
            // Ignore exceptions
        }
    }

    public function onRecordWasCreatedForDetailAction(RecordWasCreatedForDetailAction $event): void
    {
        try {
            $this->dispatcher->dispatch(
                RecordController::class,
                'beforeDetailViewRender',
                [$event->getRecordController(), $event->getRecord()]
            );
        } catch (InvalidSlotException | InvalidSlotReturnException $e) {
            // Ignore exceptions
        }
    }

    public function onVoteIfRecordShouldBeSkipped(VoteIfRecordShouldBeSkipped $event): void
    {
        $record = $event->getRecord();
        $tableName = $record->getTableName();
        $signalArguments = $this->dispatcher->dispatch(
            CommonRepository::class,
            'shouldSkipRecord',
            [
                ['yes' => 0, 'no' => 0],
                $event->getCommonRepository(),
                ['record' => $record, 'tableName' => $tableName],
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }

    public function onVoteIfRecordShouldBeIgnored(VoteIfRecordShouldBeIgnored $event): void
    {
        $signalArguments = $this->dispatcher->dispatch(
            CommonRepository::class,
            'shouldIgnoreRecord',
            [
                ['yes' => 0, 'no' => 0],
                $event->getCommonRepository(),
                [
                    'localProperties' => $event->getLocalProperties(),
                    'foreignProperties' => $event->getForeignProperties(),
                    'tableName' => $event->getTableName(),
                ],
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }
}
