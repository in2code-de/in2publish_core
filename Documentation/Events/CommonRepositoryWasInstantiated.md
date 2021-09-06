# CommonRepositoryWasInstantiated

Replaces the `\In2code\In2publishCore\Reposistory\CommonRepository / instanceCreated` Signal.

## When

Each time an instances of the CommonRepository is instantiated.

## What

* `commonRepository`: The instance of the CommonRepository which is currently being instantiated.

## Possibilities

Since the `CommonRepository` is one of the central classes in in2publish_core, you can be sure that the Content
Publisher is doing something when this event was triggered. You can use this event as central "Content Publishing is
going on" system event.
