# RecursiveRecordPublishingEnded

Replaces the `\In2code\In2publishCore\Repository\CommonRepository / publishRecordRecursiveEnd` Signal.

## When

This event will be fired when the publishing process has ended. It identifies the finish of the process and will be
dispatched once. The start of the publishing process has been identified by the
[`RecursiveRecordPublishingBegan` (link)](RecursiveRecordPublishingBegan.md) event.

## What

* `recordTree`: The root record which contains all related records which were published.

## Possibilities

Since this event designates the end of the publishing process, it is best suited to create so-called "Publishing Tasks".
These Tasks will be executed on the foreign instance. They can be used to flush caches, interact with FAL storages or
drivers, connect to Solr, Elasticsearch, LDAP or whatever exists in your foreign TYPO3 environment, which can not be
accessed by Local.

### Example

Real world examples are the best examples. Have a look at the
`\In2code\In2publishCore\Features\NewsSupport\EventListener\NewsSupportEventListener`, which is a simple event listener
that delegates the domain logic to the `NewsCacheInvalidator`, which creates the tasks.
