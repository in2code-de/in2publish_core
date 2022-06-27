# 25505-BreakingChanges

Issue https://projekte.in2code.de/issues/25505

## Description

TODO:

## Impact

Replaced ignoreFieldsForDifferenceView with ignoredFields

Removed features:
* SimplifiedOverviewAndPublishing

Removed components:
* FalHandling
* RecordHandling

Removed Events:
* RecordWasEnriched
* VoteIfFindingByIdentifierShouldBeSkipped -> DemandsWereCollected
* VoteIfFindingByPropertyShouldBeSkipped -> DemandsWereCollected
* VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped -> DemandsWereCollected
* VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped -> DemandsWereCollected
* RelatedRecordsByRteWereFetched -> DemandsForTextWereCollected
* VoteIfPageRecordEnrichingShouldBeSkipped
* VoteIfRecordShouldBeIgnored -> DecideIfRecordShouldBeIgnored
* VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped -> DemandsWereCollected
* VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped
* VoteIfSearchingForRelatedRecordsShouldBeSkipped
* RecordInstanceWasInstantiated -> RecordWasCreated
* RecordWasCreatedForDetailAction
* RecordWasSelectedForPublishing
* RecordsWereSelectedForPublishing -> RecursiveRecordPublishingBegan

## Affected Installations

All.

## Migration
