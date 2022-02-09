# TaskExecutionWasFinished

## When

This event will be dispatched after all pending tasks on foreign have been executed.

## What

* `remoteCommandResponse`: The response object of the remote command execution. The response also indicates if the
  request was successful or not.

## Possibilities

You can listen to this event to trigger a listener when the publishing of a record was truly finished.
`PublishingOfOneRecordEnded` marks the end of the publishing process which writes to the database, but does not include
tasks like the `FlushFrontendPageCacheTask`, which are required to the new content to actually be visible on Foreign.

You must not use this event to register new tasks for the current publication process. They won't be executed in the
current, but the next publication run.
