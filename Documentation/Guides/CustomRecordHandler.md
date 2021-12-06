# Custom Record Handler

_Since in2publish_core 10.2_

One of the Content Publishers core components are the record handler classes. They are responsible to search for a
record and all related records and publish them. Until in2publish_core 10.2, the `CommonRepository` did combine both
functions but was then splitted into a `RecordFinder` and `RecordPublisher` class for multiple reasons which include the
Single Responsibility Principle and Readability.

You can profit from this refactoring by implementing your own `RecordFinder` or `RecordPublisher` and set the
implementing class in the configuration `factory.finder` / `factory.publisher`. Your finder must
implement `\In2code\In2publishCore\Component\RecordHandling\RecordFinder`, your publisher must
implement `\In2code\In2publishCore\Component\RecordHandling\RecordPublisher`.

You don't have to implement both classes, as long as you want to override only one aspect of the Content Publisher.

The `\In2code\In2publishCore\Component\RecordHandling\DefaultRecordPublisher` should suffice for most cases and no
replacement should be needed, as you can control most aspects of the publishing process with events.

Replacing the `\In2code\In2publishCore\Component\RecordHandling\DefaultRecordFinder` can be quite useful if you want to
skip certain aspects of your TYPO3, the TCA, or whatever comes to your mind. However, you should try to control the
behavior using event listeners first. They come in handy for most use cases.

When you implement your own `RecordFinder` you have to abide certain rules.

* You must return one instance of `RecordInterface` despite the `nullable` return type hint.
* The record which is directly returned must contain the additional property `isRoot`, if not flagged by `$simulateRoot`
  otherwise.
* Every record must have the additional property `depth`.
* records which represent files must have the additional property `isAuthoritative` if you are going to use
  the `PhysicalFilePublisher` by triggering the event `PublishingOfOneRecordEnded`.
