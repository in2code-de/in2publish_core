# Deprecation: Old task classes

Issue https://projekte.in2code.de/issues/47934

## Description

The task execution after the publishing process has been extracted from the huge und unordered code base into a single
folder in "Component/PostPublishTaskExecution". The `TaskExecutionService` is the new, centralized API to run the
registered tasks after publishing. You should not execute the `in2publish_core:publishtasksrunner:runtasksinqueue`
directly.

## Impact

Deprecated classes:

1. `\In2code\In2publishCore\Command\Foreign\PublishTaskRunner\RunTasksInQueueCommand`
1. `\In2code\In2publishCore\Domain\Repository\TaskRepository`
1. `\In2code\In2publishCore\Domain\Factory\TaskFactory`
1. `\In2code\In2publishCore\Domain\Model\Task\AbstractTask`

## Affected Installations

All.

## Migration

If you extend or inject any of these classes then please update your dependency or parent to the new class.

RunTasksInQueueCommand:

* Old: `\In2code\In2publishCore\Command\Foreign\PublishTaskRunner\RunTasksInQueueCommand`
* New: `\In2code\In2publishCore\Component\PostPublishTaskExecution\Command\Foreign\RunTasksInQueueCommand`

TaskRepository:

* Old: `\In2code\In2publishCore\Domain\Repository\TaskRepository`
* New: `\In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepository`

TaskFactory:

* Old: `\In2coe\In2publishCore\Domain\Factory\TaskFactory`
* New: `\In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Factory\TaskFactory`

AbstractTask:

* Old: `\In2code\In2publishCore\Domain\Model\Task\AbstractTask`
* New: `\In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Model\Task\AbstractTask`
