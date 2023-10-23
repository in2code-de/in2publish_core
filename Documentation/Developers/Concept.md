# Concept

## Basics

In TYPO3, connections and relations between data are not written in the database. TYPO3 doesn't event know about foreign
keys, leaving alone foreign key constraints. TYPO3 has its own DSL-like approach to glue things together,
the [TCA](https://docs.typo3.org/m/typo3/reference-tca/main/en-us/).

The TCA must contain everything for TYPO3 and Extbase to function properly and can be seen as the single source of truth
when it comes to links between data. Therefore, the Content Publisher leverages the TCA to identify which row in the
database is connected to another and how they have to be published.

Aside from the TCA, there are some relations that are not defined in that array but just exist, like the relation
between page and content by the content's column `pid`.

The Content Publisher knows about PIDs, TCA and FlexForms (which are just another form of TCA) and uses that knowledge
to build a [tree of records](RecordTree.md), which represents the relation between records in its structure.

This record tree is used to show the user of the Content Publisher what is new, changed, moved, soft deleted or deleted.
Additionally, it is used for publishing. The record tree that the Content Publisher shows in the Overview Module is the
same that we are using for the publishing process.

## RecordTree Building Process

Since version 12 of the Content Publisher, the process of database queries and TCA lookups are separated. Since the TCA
is already available before the first database row and serves as the basis for all further steps, it is processed first.

The PreProcessors search the TCA for columns that refer to other tables or otherwise, e.g. through wizards, may contain
a link to other data sets. A corresponding resolver is created for each possible link.

Then the first record, usually the page clicked on in the Publish Overview Module, is fetched from the database.
Depending on the selected depth, we use the PID to search the subpages of the first page. The pages to be searched for
are collected in a demand object until all information has been collected. Then the demand is fulfilled. As few queries
as possible are built from the demand. These queries are executed in the local and foreign database and the rows found
are converted into DatabaseRecord objects.

When all pages have been found, a query is created for each table which will find all data records on all found pages (
via the PID).

Once all non-TCA relations have been resolved, the actual process begins. All records are passed into the resolvers from
the first step. The resolvers take the properties of the records and build the Demands object from them. Once all
records have been processed, the demand is fully built and will be fulfilled. Here again, as few queries as possible are
executed in order to find all rows that should be found and to convert them into DatabaseRecord objects.

The last step is repeated for all newly found records until no more are found or the safety limit of 8 iterations has
been reached. It is extremely unlikely that there are linking chains longer than 8.

After building the record tree, it will be modified.

1. Translated records will be connected and moved next to their translations original.
2. Every record is asked to build its [dependency](RecordDependencies.md) list.
3. The dependencies are processed. (Required records are fetched and passed to the dependencies to check if they are
   fulfilled).

## Publishing Process

Publishing the record tree is the easy part compared to building it. The record tree is traversed and each single record
is passed to a record publisher. Each publisher only accepts the record it can actually publish.
The `DatabaseRecordPublisher` can only publish objects extending `AbstractDatabaseRecord`, the `FileRecordPublisher`
only `FileRecord`, and so on. When a publisher has been found for a record, no other publisher will be asked to publish
the record.

When an error occurs during publishing the process will be rolled back, so that nothing should be published at the
end. "Should", because some publishers are not transactional and can't be rolled back and some non-transactional
publishers can not undo what they have done (e.g. deleting a file). Also, errors can occur when committing transactional
publishers. In that case, other transactions will be rolled back, but finished transactions can not be rolled back.
So there is always a risk that some but not all records are published in case of unexpected errors.
This is still a huge improvement compared to older versions of the Content Publisher, which did not have
transactions at all, and errors usually occur before committing.

You can also write a publisher for your own [`DatabaseRecord` subtype](DatabaseRecordSubType.md). Read more about
publisher in the [Record Publisher](RecordPublisher.md) docs.
