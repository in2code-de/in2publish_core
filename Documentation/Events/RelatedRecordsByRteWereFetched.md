# RelatedRecordsByRteWereFetched

Replaces the `\In2code\In2publishCore\Repository\CommonRepository / relationResolverRTE` Signal.

## When

Each time after the Content Publisher tried to resolve relations to records that are embedded in RichText or t3 URIs,
regardless whether one or more records have been found or not.

## What

* `recordFinder`: The instance of the `\In2code\In2publishCore\Component\RecordHandling\RecordFinder` which tried to
  resolve the relations.
* `bodyText`: The text that was examined by the RecordFinder
* `excludedTableNames`: The current list of table names which are excluded
* `relatedRecords`: All records found by the RecordFinder. Can be empty.

## Possibilities

This signal has been used primarily to extend the search methods with other algorithms, that detect relations based on
different clues in the text, e.g. embedded images. Records added to the event will be added to the list of found
records.
