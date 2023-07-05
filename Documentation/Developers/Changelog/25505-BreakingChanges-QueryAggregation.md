# 25505 BreakingChanges QueryAggregation

Issue https://projekte.in2code.de/issues/25505

## Description

Query Aggregation (QUAG) was introduced to reduce the number of queries and therefore the time that the Content Publisher
takes to build up and display the record tree. As the project's working title suggests, we achieve this by aggregating
queries.

Please read the section Removed Components / RecordHandling for detailed information about how we resolve relations.

## Impact

QUAG results in a noticeable performance improvement.
Therefore, features which had been offered in previous versions to overcome performance issues are now superfluous and
have therefore been removed.

### Class structure

#### General

Everything which is required for the basic functions of the Content Publisher has been moved to the component `Core`.

##### The Record Object Model

Previously, there was only one `Record` class. Aside from structural problems (size, complexity, dependencies) it also
had identity problems. First, the identity of a record was never clear. It could be a page record with an integer uid,
an MM record with a concatenated string of `uid_local` and `uid_foreign`, or a `sys_file` record representing an actual
file from a FAL storage.

The new approach takes into account that different data may have different meanings. A file on the disk is different
from a database entity, is different from an MM record, is different from the TYPO3 page tree root (ID=0), ...
To address all these issues and to overcome the shortcomings of the previous implementation, the record object model has
been reworked extensively.

Find more information in the developer documentation
about [`DatabaseRecordSubtypes` (link)](../DatabaseRecordSubType.md).

##### The RecordTree

The root of everything is not a record anymore, now it is the `RecordTree`.

Find more information in the developer documentation about [`RecordTree` (link)](../RecordTree.md).

### Configuration

#### `ignoreFieldsForDifferenceView`

The option `ignoreFieldsForDifferenceView` was replaced with `ignoredFields`. The new setting is way more powerful since
it supports regular expressions that let you ignore a single field for all or some tables that match the expression. The
migration is very easy, as `ignoredFields` has the same structure as the former configuration and you just have to
rename the key in your configuration. There is also an automatic configuration migration which converts your
old `ignoreFieldsForDifferenceView` to `ignoredFields` on the fly. You will see a warning in the Content Publisher Tests
as long as you still have a legacy configuration.

#### Other removed fields

Following configuration options have been removed, because the feature or component was removed.

* `factory.finder`
* `factory.finder`
* `factory.publisher`
* `factory.maximumPageRecursion`
* `factory.maximumContentRecursion`
* `factory.maximumOverallRecursion`
* `factory.fal.finder`
* `factory.fal.publisher`
* `factory.fal.reserveSysFileUids`
* `factory.fal.reclaimSysFileEntries`
* `factory.fal.autoRepairFolderHash`
* `factory.fal.mergeSysFileByIdentifier`
* `factory.fal.enableSysFileReferenceUpdate`
* `factory.fal.folderFileLimit`
* `debug.disableParentRecords`
* `debug.showRecordDepth`
* `debug.showExecutionTime`
* `debug.allInformation`

### Removed Features

#### SimplifiedOverviewAndPublishing (SOAP)

The purpose of SimplifiedOverviewAndPublishing was to increase the Content Publisher`s performance. This feature actually
has a long line of history, because it precedes the features SimpleOverviewAndAjax and SimplePublishing from the
Content Publisher Enterprise Edition by combining them. SOAP could be understood as a prototype or rather predecessor of
QUAG. It also did query one table at a time and in fact, the Repository classes have been maintained. SOAP, however, did
not recurse into the record relations and only resolved the first relation level. This was the big disadvantage of
that feature. It was fast but at the cost of leaving out records.

The reason that this feature is dropped is simply that the new core is as fast as SOAP but with 100% precision. So it is
like SOAP but without the drawbacks, which makes SOAP superfluous.

### Removed components:

#### FalHandling

FalHandling was the component that was responsible for building the record tree for the Publish Files Module. It was
very complex, error-prone and hard to understand and debug. In fact, it had bugs that dated back to as early as 2015, which
could never be resolved because of the way the module worked. To alleviate these pains, the FAL handling was rewritten into a
more Filelist-ish way. Previously, the files shown in the Publish Files Module had been based on the File Index records (
sys_file). Neither had this been a good idea for several reasons, nor does it represent what the Filelist Module shows us.
The Filelist Module is based on the actual files in your storage.

The Publish Files Module contents are now based on actual files, but we still rely on sys_file to detect if files have
been renamed or moved, because this information is not persisted with the file itself.

Previously, it had been possible that a file identifier showed up more than once in the Publish Files Module, because
there had been multiple sys_file entries. Now that we are file based, there will always be just one file identifier
displayed and publishing a file will publish all its associated index records.

#### RecordHandling

QUAG replaces the RecordHandling.

##### How did the Content Publisher work before QUAG?

Before QUAG, the Content Publisher queried the Database for every single record. If it was a page, it would then query all
tables that could contain records which lived on the page. Upon the first record found, the Content Publisher would look at
the record's TCA and identify all columns that pointed to another table. Then, the Content Publisher would query each table
from the TCA until a new record was found. The Content Publisher examined the record's TCA for columns that pointed to
other tables and the process was repeated recursively, until no more records were found.

In other words, the old version of the Content Publisher queried the database for *each possible record*. This means that
for a single page record, the Content Publisher would produce 9 queries. For a tt_content record it produced 12 queries.

Additionally, it was virtually impossible to identify bugs that occurred at deep recursion levels, especially for bugs
that occurred at deep nesting levels, which include FlexForms on customer instances without the possibility to debug
properly.

All these pains will vanish with our completely new all-fresh prepare-execute-order approach.

##### How does QUAG work?

In QUAG we first separate the process of database queries and TCA lookups. Since the TCA is already available before the
first database row and serves as the basis for all further steps, it is processed first.

The PreProcessors search the TCA for columns that refer to other tables or may contain a link to other data by other means,
e.g. through wizards. A corresponding resolver is created for each possible link.

Then the first record, usually the page clicked in the Publish Overview Module, is fetched from the database.
Depending on the selected depth, we use the PID to search the subpages of the first page. The pages to be searched for
are collected in a demand object until all information has been collected. Then the demand is resolved. As few queries
as possible are built from the demand. These queries are executed in the local and foreign database and the rows found
are converted into DatabaseRecord objects.

When all pages have been found, a query is created for each table which finds all data records on all found pages (via the PID).

Once all non-TCA relations have been resolved, the actual process begins. All records are passed into the resolvers from
the first step. The resolvers take the properties of the records and build the Demands object from them. Once all
records have been processed, the demand is fully built and will be resolved. Here again, as few queries as possible are
executed in order to find all rows that should be found and to convert them into DatabaseRecord objects.

The last step is repeated for all newly found records until no more records are found or the safety limit of 8 iterations
has been reached. It is extremely unlikely that there are linking chains longer than 8.

### Remove/Replaced Events

#### Events removed without replacement

* `\In2code\In2publishCore\Event\RecordWasEnriched`
* `\In2code\In2publishCore\Event\RecordWasCreatedForDetailAction`
* `\In2code\In2publishCore\Event\RecordWasSelectedForPublishing`
* `\In2code\In2publishCore\Event\VoteIfPageRecordEnrichingShouldBeSkipped`

#### Events with replacement

Following events have been removed, but you can listen
to [`\In2code\In2publishCore\Event\DemandsWereCollected`(link)](../Events/DemandsWereCollected.md) to achieve the same
result.

* `\In2code\In2publishCore\Event\VoteIfFindingByIdentifierShouldBeSkipped`
* `\In2code\In2publishCore\Event\VoteIfFindingByPropertyShouldBeSkipped`
* `\In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByPropertyShouldBeSkipped` (Or [ExcludeRecordsByParentRecord](../Guides/ExcludeRecordsByParentRecord.md))
* `\In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByTableShouldBeSkipped`
* `\In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByFlexFormPropertyShouldBeSkipped`
* `\In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsByFlexFormShouldBeSkipped`
* `\In2code\In2publishCore\Event\VoteIfSearchingForRelatedRecordsShouldBeSkipped`

Following events have been removed, but you can listen
to [`\In2code\In2publishCore\Event\RecordRelationsWereResolved`(link)](../Events/RecordRelationsWereResolved.md) to
achieve the same result.

* `\In2code\In2publishCore\Event\AllRelatedRecordsWereAddedToOneRecord`
* `\In2code\In2publishCore\Event\RootRecordCreationWasFinished`

Following events have been merged
into [`\In2code\In2publishCore\Event\RecordWasCreated`(link)](../Events/RecordWasCreated.md)

* `\In2code\In2publishCore\Event\RecordInstanceWasInstantiated`
* `\In2code\In2publishCore\Event\FolderInstanceWasCreated`

Following events can be replaced with the publishing event:

* `\In2code\In2publishCore\Event\RecordsWereSelectedForPublishing`
  -> [`\In2code\In2publishCore\Event\RecursiveRecordPublishingBegan`(link)](../Events/RecursiveRecordPublishingBegan.md)
* `\In2code\In2publishCore\Event\PhysicalFileWasPublished`
  -> [`\In2code\In2publishCore\Event\PublishingOfOneRecordEnded`(link)](../Events/PublishingOfOneRecordEnded.md)
* `\In2code\In2publishCore\Event\FolderWasPublished`
  -> [`\In2code\In2publishCore\Event\PublishingOfOneRecordEnded`(link)](../Events/PublishingOfOneRecordEnded.md)

Following events were renamed:

* `\In2code\In2publishCore\Event\RelatedRecordsByRteWereFetched`
  -> [`\In2code\In2publishCore\Event\DemandsForTextWereCollected`(link)](../Events/DemandsForTextWereCollected.md)
* `\In2code\In2publishCore\Event\VoteIfRecordShouldBeIgnored`
  -> [`\In2code\In2publishCore\Event\DecideIfRecordShouldBeIgnored`(link)](../Events/DecideIfRecordShouldBeIgnored.md)
* `\In2code\In2publishCore\Event\VoteIfRecordShouldBeSkipped`
  -> [`\In2code\In2publishCore\Event\CollectReasonsWhyTheRecordIsNotPublishable`(link)](../Events/CollectReasonsWhyTheRecordIsNotPublishable.md)

## Affected Installations

All.

## Migration

Because of the massive changes, each section contains its migration instructions itself.
