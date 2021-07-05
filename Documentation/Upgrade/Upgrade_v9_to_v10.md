Replace signal slots by PSR-14 events

https://docs.typo3.org/c/typo3/cms-core/master/en-us/Changelog/10.0/Feature-88770-PSR-14BasedEventDispatcher.html
https://usetypo3.com/psr-14-events.html


The following signal slots were replaced by PSR-14 events

| Signal slot                            | Event                                   |
| ---------------------------------------|-----------------------------------------|
| beforeDetailViewRender                 | RecordWasCreatedForDetailAction         |
| beforePublishing                       | RecordWasSelectedForPublishing          |
| collectSupportPlaces                   | RreatedDefaultHelpLabels                |
| instanceCreated  (RecordFactory)       | RecordInstanceWasInstantiated           |
| instanceCreated  (CommonRepository)    | CommonRepositoryWasInstantiated         |
| rootRecordFinished                     | RootRecordCreationWasFinished           |
| addAdditionalRelatedRecords            | AllRelatedRecordsWereAddedToOneRecord   |
| afterRecordEnrichment (deprecated!)    | RecordWasEnriched                       |
| relationResolverRTE                    | RelatedRecordsByRteWereFetched          |
| publishRecordRecursiveBegin            | RecursiveRecordPublishingBegan          |
| publishRecordRecursiveEnd              | RecursiveRecordPublishingEnded          |
| publishRecordRecursiveBeforePublishing | PublishingOfOneRecordBegan              |
| publishRecordRecursiveAfterPublishing  | PublishingOfOneRecordEnded              |
| filterStorages                         | StoragesForTestingWereFetched           |
| isPublishable                          | VoteIfRecordIsPublishable               |
| shouldSkipRecord                       | VoteIfRecordShouldBeSkipped             |
| shouldIgnoreRecord                     | VoteIfRecordShouldBeIgnored             |
| shouldSkipEnrichingPageRecord          | VoteIfPageRecordEnrichingShouldBeSkipped |
| shouldSkipFindByIdentifier             | VoteIfFindingByIdentifierShouldBeSkipped |
| shouldSkipFindByProperty               | VoteIfFindingByPropertyShouldBeSkipped  |
| shouldSkipSearchingForRelatedRecordByTable              | VoteIfSearchingForRelatedRecordByTableShouldBeSkipped  |
| shouldSkipSearchingForRelatedRecords                    | VoteIfSearchingForRelatedRecordsShouldBeSkipped  |
| shouldSkipSearchingForRelatedRecordsByFlexForm          | VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped  |
| shouldSkipSearchingForRelatedRecordsByFlexFormProperty  | VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped  |
| shouldSkipSearchingForRelatedRecordsByProperty          | VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped  |


