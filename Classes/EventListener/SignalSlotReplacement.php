<?php

declare(strict_types=1);

namespace In2code\In2publishCore\EventListener;

use In2code\In2publishCore\Controller\FileController;
use In2code\In2publishCore\Controller\RecordController;
use In2code\In2publishCore\Controller\ToolsController;
use In2code\In2publishCore\Domain\Factory\RecordFactory;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord;
use In2code\In2publishCore\Event\CommonRepositoryWasInstantiated;
use In2code\In2publishCore\Event\CreatedDefaultHelpLabels;
use In2code\In2publishCore\Event\FolderInstanceWasCreated;
use In2code\In2publishCore\Event\PublishingOfOneRecordBegan;
use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;
use In2code\In2publishCore\Event\RecordInstanceWasInstantiated;
use In2code\In2publishCore\Event\RecordWasCreatedForDetailAction;
use In2code\In2publishCore\Event\RecordWasEnriched;
use In2code\In2publishCore\Event\RecordWasSelectedForPublishing;
use In2code\In2publishCore\Event\RecursiveRecordPublishingBegan;
use In2code\In2publishCore\Event\RecursiveRecordPublishingEnded;
use In2code\In2publishCore\Event\RelatedRecordsByRteWereFetched;
use In2code\In2publishCore\Event\RootRecordCreationWasFinished;
use In2code\In2publishCore\Event\StoragesForTestingWereFetched;
use In2code\In2publishCore\Event\VoteIfFindingByIdentifierShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfFindingByPropertyShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfPageRecordEnrichingShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfRecordIsPublishable;
use In2code\In2publishCore\Event\VoteIfRecordShouldBeIgnored;
use In2code\In2publishCore\Event\VoteIfRecordShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped;
use In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsShouldBeSkipped;
use In2code\In2publishCore\Testing\Data\FalStorageTestSubjectsProvider;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException;
use TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException;

class SignalSlotReplacement
{
    /** @var Dispatcher */
    protected $dispatcher;

    /** @var LogManager */
    protected $logManager;

    public function __construct(Dispatcher $dispatcher, LogManager $logManager)
    {
        $this->dispatcher = $dispatcher;
        $this->logManager = $logManager;
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

    public function onVoteIfPageRecordEnrichingShouldBeSkipped(VoteIfPageRecordEnrichingShouldBeSkipped $event): void
    {
        $signalArguments = $this->dispatcher->dispatch(
            CommonRepository::class,
            'shouldSkipEnrichingPageRecord',
            [
                ['yes' => 0, 'no' => 0],
                $event->getCommonRepository(),
                [
                    'record' => $event->getRecord(),
                ],
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }

    public function onVoteIfFindingByIdentifierShouldBeSkipped(VoteIfFindingByIdentifierShouldBeSkipped $event): void
    {
        $signalArguments = $this->dispatcher->dispatch(
            CommonRepository::class,
            'shouldSkipFindByIdentifier',
            [
                ['yes' => 0, 'no' => 0],
                $event->getCommonRepository(),
                [
                    'identifier' => $event->getIdentifier(),
                    'tableName' => $event->getTableName(),
                ],
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }

    public function onVoteIfFindingByPropertyShouldBeSkipped(VoteIfFindingByPropertyShouldBeSkipped $event): void
    {
        $signalArguments = $this->dispatcher->dispatch(
            CommonRepository::class,
            'shouldSkipFindByProperty',
            [
                ['yes' => 0, 'no' => 0],
                $event->getCommonRepository(),
                [
                    'propertyName' => $event->getPropertyName(),
                    'propertyValue' => $event->getPropertyValue(),
                    'tableName' => $event->getTableName(),
                ],
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }

    public function onVoteIfSearchingForRelatedRecordsByTableShouldBeSkipped(
        VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped $event
    ): void {
        $signalArguments = $this->dispatcher->dispatch(
            CommonRepository::class,
            'shouldSkipSearchingForRelatedRecordByTable',
            [
                ['yes' => 0, 'no' => 0],
                $event->getCommonRepository(),
                [
                    'record' => $event->getRecord(),
                    'tableName' => $event->getTableName(),
                ],
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }

    public function onVoteIfSearchingForRelatedRecordsShouldBeSkipped(
        VoteIfSearchingForRelatedRecordsShouldBeSkipped $event
    ): void {
        $signalArguments = $this->dispatcher->dispatch(
            CommonRepository::class,
            'shouldSkipSearchingForRelatedRecords',
            [
                ['yes' => 0, 'no' => 0],
                $event->getCommonRepository(),
                [
                    'record' => $event->getRecord(),
                ],
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }

    public function onVoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped(
        VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped $event
    ): void {
        $signalArguments = $this->dispatcher->dispatch(
            CommonRepository::class,
            'shouldSkipSearchingForRelatedRecordsByFlexForm',
            [
                ['yes' => 0, 'no' => 0],
                $event->getCommonRepository(),
                [
                    'record' => $event->getRecord(),
                    'column' => $event->getColumn(),
                    'columnConfiguration' => $event->getColumnConfiguration(),
                    'flexFormDefinition' => $event->getFlexFormDefinition(),
                    'flexFormData' => $event->getFlexFormData(),
                ],
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }

    public function onVoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped(
        VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped $event
    ): void {
        $signalArguments = $this->dispatcher->dispatch(
            CommonRepository::class,
            'shouldSkipSearchingForRelatedRecordsByFlexFormProperty',
            [
                ['yes' => 0, 'no' => 0],
                $event->getCommonRepository(),
                [
                    'record' => $event->getRecord(),
                    'config' => $event->getConfig(),
                    'flexFormData' => $event->getFlexFormData(),
                ],
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }

    public function onVoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped(
        VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped $event
    ): void {
        $signalArguments = $this->dispatcher->dispatch(
            CommonRepository::class,
            'shouldSkipSearchingForRelatedRecordsByProperty',
            [
                ['yes' => 0, 'no' => 0],
                $event->getCommonRepository(),
                [
                    'record' => $event->getRecord(),
                    'propertyName' => $event->getPropertyName(),
                    'columnConfiguration' => $event->getColumnConfiguration(),
                ],
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }

    public function onVoteIfRecordIsPublishable(VoteIfRecordIsPublishable $event): void
    {
        $signalArguments = $this->dispatcher->dispatch(
            RecordInterface::class,
            'isPublishable',
            [
                ['yes' => 0, 'no' => 0],
                $event->getTable(),
                $event->getIdentifier(),
            ]
        );
        $event->voteYes($signalArguments[0]['yes']);
        $event->voteNo($signalArguments[0]['no']);
    }

    public function onRecordInstanceWasInstantiated(RecordInstanceWasInstantiated $event): void
    {
        try {
            $this->dispatcher->dispatch(
                RecordFactory::class,
                'instanceCreated',
                [
                    $event->getRecordFactory(),
                    $event->getRecord(),
                ]
            );
        } catch (InvalidSlotException | InvalidSlotReturnException $e) {
            // Ignore exceptions
        }
    }

    public function onRootRecordCreationWasFinished(RootRecordCreationWasFinished $event): void
    {
        try {
            $this->dispatcher->dispatch(
                RecordFactory::class,
                'rootRecordFinished',
                [
                    $event->getRecordFactory(),
                    $event->getRecord(),
                ]
            );
        } catch (InvalidSlotException | InvalidSlotReturnException $e) {
            // Ignore exceptions
        }
    }

    public function onAllRelatedRecordsWereAddedToOneRecord(AllRelatedRecordsWereAddedToOneRecord $event): void
    {
        $this->dispatcher->dispatch(
            RecordFactory::class,
            'addAdditionalRelatedRecords',
            [
                $event->getRecord(),
                $event->getRecordFactory(),
            ]
        );
    }

    public function onCommonRepositoryWasInstantiated(CommonRepositoryWasInstantiated $event): void
    {
        $this->dispatcher->dispatch(
            CommonRepository::class,
            'instanceCreated',
            [
                $event->getCommonRepository()
            ]
        );
    }

    public function onRelatedRecordsByRteWereFetched(RelatedRecordsByRteWereFetched $event): void
    {
        $relatedRecords = $event->getRelatedRecords();
        try {
            $this->dispatcher->dispatch(
                CommonRepository::class,
                CommonRepository::SIGNAL_RELATION_RESOLVER_RTE,
                [
                    $event->getCommonRepository(),
                    $event->getBodyText(),
                    $event->getExcludedTableNames(),
                    &$relatedRecords
                ]
            );
            $event->setRelatedRecords($relatedRecords);
        } catch (InvalidSlotException | InvalidSlotReturnException $e) {
            $logger = $this->logManager->getLogger(CommonRepository::class);
            $logger->error(
                'Exception during signal dispatching',
                [
                    'exception' => $e,
                    'signalClass' => CommonRepository::class,
                    'signalName' => CommonRepository::SIGNAL_RELATION_RESOLVER_RTE,
                ]
            );
        }
    }

    public function onRecursiveRecordPublishingBegan(RecursiveRecordPublishingBegan $event): void
    {
        $this->dispatcher->dispatch(
            CommonRepository::class,
            'publishRecordRecursiveBegin',
            [
                $event->getRecord(),
                $event->getCommonRepository()
            ]
        );
    }

    public function onRecursiveRecordPublishingEnded(RecursiveRecordPublishingEnded $event): void
    {
        $this->dispatcher->dispatch(
            CommonRepository::class,
            'publishRecordRecursiveEnd',
            [
                $event->getRecord(),
                $event->getCommonRepository()
            ]
        );
    }

    public function onPublishingOfOneRecordBegan(PublishingOfOneRecordBegan $event): void
    {
        $record = $event->getRecord();
        $this->dispatcher->dispatch(
            CommonRepository::class,
            'publishRecordRecursiveBeforePublishing',
            [
                $record->getTableName(),
                $record,
                $event->getCommonRepository()
            ]
        );
    }

    public function onPublishingOfOneRecordEnded(PublishingOfOneRecordEnded $event): void
    {
        $record = $event->getRecord();
        $this->dispatcher->dispatch(
            CommonRepository::class,
            'publishRecordRecursiveAfterPublishing',
            [
                $record->getTableName(),
                $record,
                $event->getCommonRepository()
            ]
        );
    }

    public function onRecordWasEnriched(RecordWasEnriched $event): void
    {
        $record = $event->getRecord();
        try {
            [$record] = $this->dispatcher->dispatch(
                CommonRepository::class,
                'afterRecordEnrichment',
                [$record]
            );
            $event->setRecord($record);
        } catch (InvalidSlotException | InvalidSlotReturnException $e) {
            // ignore exception
        }
    }

    public function onRecordWasSelectedForPublishing(RecordWasSelectedForPublishing $event): void
    {
        try {
            $this->dispatcher->dispatch(
                RecordController::class,
                'beforePublishing',
                [
                    $event->getRecordController(),
                    $event->getRecord(),
                ]
            );
        } catch (InvalidSlotException | InvalidSlotReturnException $e) {
            // ignore exception
        }
    }

    public function onCreatedDefaultHelpLabels(CreatedDefaultHelpLabels $event): void
    {
        $this->dispatcher->dispatch(
            ToolsController::class,
            'collectSupportPlaces',
            [
                $event->getSupports()
            ]
        );
    }

    public function onStoragesForTestingWereFetched(StoragesForTestingWereFetched $event): void
    {
        $arguments = [
            'localStorages' => $event->getLocalStorages(),
            'foreignStorages' => $event->getForeignStorages(),
            'purpose' => $event->getPurpose(),
        ];
        try {
            $arguments = $this->dispatcher->dispatch(
                FalStorageTestSubjectsProvider::class,
                'filterStorages',
                $arguments
            );
            $event->setLocalStorages($arguments['localStorages']);
            $event->setForeignStorages($arguments['foreignStorages']);
        } catch (InvalidSlotException | InvalidSlotReturnException $e) {
            // ignore exception
        }
    }
}
