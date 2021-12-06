# Deprecation: CommonRepository

Issue https://projekte.in2code.de/issues/48403

## Description

The class `CommonRepository` has been the core of the Content Publisher for many years. It grew over time and got so big
that refactoring it has become a necessity, before its size and complexity gets out of hand.

## Impact

* The class `\In2code\In2publishCore\Domain\Repository\CommonRepository` is deprecated and all of its methods are moved
  to other classes.
* All events which transport an instance of the `CommonRepository` now transport either an instance of `RecordFinder`
  or `RecordPublisher` depending on the event. The method `getCommonRepository` of these events is deprecated.
    * `\In2code\In2publishCore\Event\PublishingOfOneRecordBegan::getCommonRepository`
    * `\In2code\In2publishCore\Event\PublishingOfOneRecordEnded::getCommonRepository`
    * `\In2code\In2publishCore\Event\RecursiveRecordPublishingBegan::getCommonRepository`
    * `\In2code\In2publishCore\Event\RecursiveRecordPublishingEnded::getCommonRepository`
    * `\In2code\In2publishCore\Event\RelatedRecordsByRteWereFetched::getCommonRepository`
    * `\In2code\In2publishCore\Event\VoteIfFindingByIdentifierShouldBeSkipped::getCommonRepository`
    * `\In2code\In2publishCore\Event\VoteIfFindingByPropertyShouldBeSkipped::getCommonRepository`
    * `\In2code\In2publishCore\Event\VoteIfPageRecordEnrichingShouldBeSkipped::getCommonRepository`
    * `\In2code\In2publishCore\Event\VoteIfRecordShouldBeIgnored::getCommonRepository`
    * `\In2code\In2publishCore\Event\VoteIfRecordShouldBeSkipped::getCommonRepository`
    * `\In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped::getCommonRepository`
    * `\In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped::getCommonRepository`
    * `\In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped::getCommonRepository`
    * `\In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped::getCommonRepository`
    * `\In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsShouldBeSkipped::getCommonRepository`
* Legacy signals (removed in in2publish_core v11) which replace the events will contain an instance of `RecordFinder`
  or `RecordPublisher` depending on the event. These classes are still type-compatible with `CommonRepository` but do
  not contain all methods the `CommonRepository` did.

## Affected Installations

All.

## Migration

Instead of using the `CommonRepository` you can inject or get via `GeneralUtility::makeInstance` either
the `RecordFinder` or `RecordPublisher` interface depending on your use case.

Before:

```php
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$commonRepository = GeneralUtility::makeInstance(CommonRepository::class);
$record = $commonRepository->findByIdentifier(1, 'pages')
$commonRepository->publishRecordRecursive($record);
```

After:

```php
use In2code\In2publishCore\Component\RecordHandling\RecordFinder;
use In2code\In2publishCore\Component\RecordHandling\RecordPublisher;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$recordFinder = GeneralUtility::makeInstance(RecordFinder::class);
$record = $recordFinder->findRecordByUid(1, 'pages');

$recordPublisher = GeneralUtility::makeInstance(RecordPublisher::class);
$recordPublisher->publishRecordRecursive($record);
```
