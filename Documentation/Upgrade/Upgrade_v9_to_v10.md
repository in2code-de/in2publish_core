# Upgrading instructions vor in2publish_core from v9 to v10

## Replace signal slots by PSR-14 events

TYPO3 deprecated the Signal Slot Dispatcher in TYPO3 v10 and introduced PSR-14 Events as a replacement. To keep track
with this change, all Content Publisher Signals are replaced with Events, too. This change is backwards compatible in
the version 10 of in2publish_core. The backwards compatibility layer will be removed in in2publish_core v11, because
that version will only be compatible with TYPO3 v11, where the Signal Slot Dispatcher will be removed.

* https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/10.0/Feature-88770-PSR-14BasedEventDispatcher.html
* https://usetypo3.com/psr-14-events.html

The following table lists all signals of in2publish_core with their event replacement:

| Signal Class `\In2code\In2publishCore\`     | Signal Name                                            | Event
|---------------------------------------------| -------------------------------------------------------|---
| Domain\Model\RecordInterface                | isPublishable                                          | [VoteIfRecordIsPublishable](../Events/VoteIfRecordIsPublishable.md)
| Domain\Factory\RecordFactory                | instanceCreated (RecordFactory)                        | [RecordInstanceWasInstantiated](../Events/RecordInstanceWasInstantiated.md)
| Domain\Factory\RecordFactory                | rootRecordFinished                                     | [RootRecordCreationWasFinished](../Events/RootRecordCreationWasFinished.md)
| Domain\Factory\RecordFactory                | addAdditionalRelatedRecords                            | [AllRelatedRecordsWereAddedToOneRecord](../Events/AllRelatedRecordsWereAddedToOneRecord.md)
| Domain\Repository\CommonRepository          | instanceCreated (CommonRepository)                     | [CommonRepositoryWasInstantiated](../Events/CommonRepositoryWasInstantiated.md)
| Domain\Repository\CommonRepository          | afterRecordEnrichment (deprecated!)                    | [RecordWasEnriched](../Events/RecordWasEnriched.md)
| Domain\Repository\CommonRepository          | relationResolverRTE                                    | [RelatedRecordsByRteWereFetched](../Events/RelatedRecordsByRteWereFetched.md)
| Domain\Repository\CommonRepository          | publishRecordRecursiveBegin                            | [RecursiveRecordPublishingBegan](../Events/RecursiveRecordPublishingBegan.md)
| Domain\Repository\CommonRepository          | publishRecordRecursiveEnd                              | [RecursiveRecordPublishingEnded](../Events/RecursiveRecordPublishingEnded.md)
| Domain\Repository\CommonRepository          | publishRecordRecursiveBeforePublishing                 | [PublishingOfOneRecordBegan](../Events/PublishingOfOneRecordBegan.md)
| Domain\Repository\CommonRepository          | publishRecordRecursiveAfterPublishing                  | [PublishingOfOneRecordEnded](../Events/PublishingOfOneRecordEnded.md)
| Domain\Repository\CommonRepository          | shouldSkipRecord                                       | [VoteIfRecordShouldBeSkipped](../Events/VoteIfRecordShouldBeSkipped.md)
| Domain\Repository\CommonRepository          | shouldIgnoreRecord                                     | [VoteIfRecordShouldBeIgnored](../Events/VoteIfRecordShouldBeIgnored.md)
| Domain\Repository\CommonRepository          | shouldSkipEnrichingPageRecord                          | [VoteIfPageRecordEnrichingShouldBeSkipped](../Events/VoteIfPageRecordEnrichingShouldBeSkipped.md)
| Domain\Repository\CommonRepository          | shouldSkipFindByIdentifier                             | [VoteIfFindingByIdentifierShouldBeSkipped](../Events/VoteIfFindingByIdentifierShouldBeSkipped.md)
| Domain\Repository\CommonRepository          | shouldSkipFindByProperty                               | [VoteIfFindingByPropertyShouldBeSkipped](../Events/VoteIfFindingByPropertyShouldBeSkipped.md)
| Domain\Repository\CommonRepository          | shouldSkipSearchingForRelatedRecordByTable             | [VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped](../Events/VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped.md)
| Domain\Repository\CommonRepository          | shouldSkipSearchingForRelatedRecords                   | [VoteIfSearchingForRelatedRecordsShouldBeSkipped](../Events/VoteIfSearchingForRelatedRecordsShouldBeSkipped.md)
| Domain\Repository\CommonRepository          | shouldSkipSearchingForRelatedRecordsByFlexForm         | [VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped](../Events/VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped.md)
| Domain\Repository\CommonRepository          | shouldSkipSearchingForRelatedRecordsByFlexFormProperty | [VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped](../Events/VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped.md)
| Domain\Repository\CommonRepository          | shouldSkipSearchingForRelatedRecordsByProperty         | [VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped](../Events/VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped.md)
| Controller\FileController                   | folderInstanceCreated                                  | [FolderInstanceWasCreated](../Events/FolderInstanceWasCreated.md)
| Controller\RecordController                 | beforeDetailViewRender                                 | [RecordWasCreatedForDetailAction](../Events/RecordWasCreatedForDetailAction.md)
| Controller\RecordController                 | beforePublishing                                       | [RecordWasSelectedForPublishing](../Events/RecordWasSelectedForPublishing.md)
| Controller\ToolsController                  | collectSupportPlaces                                   | [CreatedDefaultHelpLabels](../Events/CreatedDefaultHelpLabels.md)
| Testing\Data\FalStorageTestSubjectsProvider | filterStorages                                         | [StoragesForTestingWereFetched](../Events/StoragesForTestingWereFetched.md)
