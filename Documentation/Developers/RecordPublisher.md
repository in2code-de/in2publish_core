# Record Publisher

Record Publishers are objects that are responsible to actually transfer data (database rows, files, folders, ...) from
local to foreign. There are different types of record publisher, each responsible for one type of record.
See [`DatabaseRecord` subtypes](DatabaseRecordSubType.md) for record types and subtypes.

The main publisher is the `DatabaseRecordPublisher`. It is best suited to explain how a publisher works. First of all,
it implements `Publisher` to qualify as an actual record publisher. The dependency injection will take care to make the
publisher known to the Content Publisher, so that it's used in the publishing process.

Publishers must implement two methods, `canPublish` and `publish`. The first method is used to check if the publisher is
capable of publishing the given record, the latter is called for all records the publisher can publish. `publish` has to
implement or delegate the logic of publishing a record, like inserting or updating a row in a database, writing a file
to disk, call an API to create or modify a resource and so on.

The interface `FinishablePublisher` tells the Content Publisher, that the publishing process needs a finish call. The
`finish` method will be called after all records were published. This finish method could be used to execute additional
tasks based on the published records.

The `TransactionalPublisher` interface extends `FinishablePublisher` and has the methods `start` and `cancel`. Speaking
of the `DatabaseRecordPublisher`, the `start` call begins a new database transaction, the call to `finish` will commit
it and `cancel` will trigger the rollback. Only publishers which are truly transactional are allowed to implement this
interface. The method `cancel` is called when something fails during publishing or committing.

The `ReversiblePublisher` interface contains only the method `reverse`. The reverse method is called when something
fails during publishing or committing. Only publisher which do reversible actions, like deleting a created file or API
resource are allowed to implement this interface.

## Publisher Order

Since reversible and transactional publishers lower the risk of partial publication, they are preferred over publishers
that do not implement these interfaces. Publishers get "points" for implementing interfaces:

* ReversiblePublisher: 2 point
* TransactionalPublisher: 1 points

A publisher implementing both will get 3 points and will always take precedence over a publisher which only implements
one or none of the interfaces. A reversible one will be preferred to a transactional one. A transactional or
reversible one will always be preferred to a publisher that does not implement any of these two interfaces.
The `FinishablePublisher` interface does not give points.

Publishers that have the same number of points are taken in the order the dependency injection detects them. There is
currently no possibility to take precedence over a specific publisher.
