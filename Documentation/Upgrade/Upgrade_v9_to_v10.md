Replace signal slots by PSR-14 events

* https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/10.0/Feature-88770-PSR-14BasedEventDispatcher.html
* https://usetypo3.com/psr-14-events.html

The following signal slots were replaced by PSR-14 events:

| Signal Class `\In2code\In2publishCore\`     | Signal Name                                            | Event
|---------------------------------------------| -------------------------------------------------------|---
| Domain\Model\RecordInterface                | isPublishable                                          | VoteIfRecordIsPublishable
| Domain\Factory\RecordFactory                | instanceCreated (RecordFactory)                        | RecordInstanceWasInstantiated
| Domain\Factory\RecordFactory                | rootRecordFinished                                     | RootRecordCreationWasFinished
| Domain\Factory\RecordFactory                | addAdditionalRelatedRecords                            | AllRelatedRecordsWereAddedToOneRecord
| Domain\Repository\CommonRepository          | instanceCreated (CommonRepository)                     | CommonRepositoryWasInstantiated
| Domain\Repository\CommonRepository          | afterRecordEnrichment (deprecated!)                    | RecordWasEnriched
| Domain\Repository\CommonRepository          | relationResolverRTE                                    | RelatedRecordsByRteWereFetched
| Domain\Repository\CommonRepository          | publishRecordRecursiveBegin                            | RecursiveRecordPublishingBegan
| Domain\Repository\CommonRepository          | publishRecordRecursiveEnd                              | RecursiveRecordPublishingEnded
| Domain\Repository\CommonRepository          | publishRecordRecursiveBeforePublishing                 | PublishingOfOneRecordBegan
| Domain\Repository\CommonRepository          | publishRecordRecursiveAfterPublishing                  | PublishingOfOneRecordEnded
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
| Controller\RecordController                 | beforePublishing                                       | RecordWasSelectedForPublishing
| Controller\ToolsController                  | collectSupportPlaces                                   | RreatedDefaultHelpLabels
| Testing\Data\FalStorageTestSubjectsProvider | filterStorages                                         | StoragesForTestingWereFetched
