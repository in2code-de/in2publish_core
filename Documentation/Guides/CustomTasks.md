# Custom Tasks

**Publishing Tasks** are pieces of code that are executed after the publishing process.
They are different from other executed code by the fact that the context of the execution is on foreign, hence you access the foreign database, access foreign's FAL, and run with foreign's configuration.
With the help of "custom tasks" you can tell the foreign system to perform actions after you have published records.

## How does it work?

All you need to begin with is a `Task`, which registers itself for execution.

**MyTask.php**

```PHP
<?php

declare(strict_types=1);

namespace YourVendor\YourPackage\Domain\Task;

use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MyTask extends \In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Model\Task\AbstractTask
{
    protected function executeTask(): bool
    {
        // Code to execute on foreign
        return true;
    }

    public function register()
    {
        $taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
        $taskRepository->add($this);
    }
}
```

Most of the time, you want to add a task when a specific record was published. You can achieve this by using the `register` method as an event listener.

**Services.yaml**

```yaml
  YourVendor\YourPackage\Domain\Task\MyTask:
    tags:
      - name: event.listener
        identifier: 'mytask-myeventlistener'
        method: 'register'
        event: In2code\In2publishCore\Event\PublishingOfOneRecordEnded
```

When a record was published, the `register` method will be called. We can not filter for specific record, e.g. for a table and property.

```PHP
use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Model\Task\AbstractTask;use In2code\In2publishCore\Event\PublishingOfOneRecordEnded;

class MyTask extends AbstractTask
{
    protected function executeTask(): bool
    {
        // Code to execute on foreign
        return true;
    }

    public function register(PublishingOfOneRecordEnded $event)
    {
        $record = $event->getRecord();
        if (
            'tx_mytemplate_domain_model_example' === $record->getTableName()
            && 3 < $record->getLocalProperty('tx_myfield')
        ) {
            $taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
            $taskRepository->add($this);
        }
    }
}
```

BTW: This is not a good class design. The responsibility of the Task is to execute code on foreign, not to register itself. You should use an anomaly for that (described later on).

**Attention: You can register Tasks multiple times.
If you use a signal that is fired for each published record you might end up with thousands of tasks registered during the publishing process.
These tasks will be executed on foreign and can temporarily adversely affect performance and make the publishing process very slow.**

Events that are suited for task registration because they are fired once are:

* `\In2code\In2publishCore\Event\RecordWasSelectedForPublishing`
* `\In2code\In2publishCore\Event\RecursiveRecordPublishingBegan`
* `\In2code\In2publishCore\Event\RecursiveRecordPublishingEnded`

## Collect data

Tasks are used to flush caches, refresh indices or to do other things that are not covered by the sole process of writing records to the foreign database.
Most tasks require a configuration, e.g. the `FlushFrontendPageCacheTask` needs to know all affected PIDs to clear the caches for these pages.
in2publish_core utilizes so called _Anomalies_ to collect relevant data during the publishing process (Anomalies were originally intended to publish non-database information/stuff like files).

Basically you need to implement three parts:

1. **Create the anomaly** (must be a `Singleton` to work correctly)
1. **Connect the anomaly to the signals** that provide the required information e.g. by utilizing the signal "publishRecordRecursiveBeforePublishing" you can collect data about all published records.
1. **Create a Task** that will be registered

During runtime your code has to:

1. (optional) **Collect the data** required for task execution.
1. **Instantiate the task** with the collected information.
1. **Register (persist) the task** in the TaskRepository.

### Example

#### Collector Class (Anomaly)

This class will collect all data you will use in your task.
It is wise to only collect information your tasks requires. Do not `clone` records. If you need to memorize the publishing state of a record (that will change during the publishing process) save it in an array next to the record. (Variables containing objects "point" to the object and do not require a lot of memory).
The method `collectInfo` is called by the signal dispatcher.

```PHP
<?php

declare(strict_types=1);

namespace YourVendor\YourPackage\Domain\Anomaly;

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository\TaskRepository;
use TYPO3\CMS\Core\SingletonInterface;use TYPO3\CMS\Core\Utility\GeneralUtility;
use YourVendor\YourPackage\Domain\Task\MyTask;

class MyAnomaly implements SingletonInterface
{
    protected array $data = [];

    public function collectInfo(string $tableName, RecordInterface $record)
    {
        // Since the MyAnomaly is a singleton the $data array will grow with each new table.
        // Slot classes that are not Singletons will be created newly for each signal dispatch and therefore can't collect data.
        $this->data[$tableName] = true;
    }

    public function writeTask()
    {
        $taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
        // Provide the task config in a format the task can use it without transformation.
        // Keep the configuration as small as possible. You can set about 4 GB but you REALLY shouldn't!
        $taskConfig = ['tables' => $this->data];

        $task = GeneralUtility::makeInstance(MyTask::class, $taskConfig);
        $taskRepository->add($task);
    }
}
```

#### Event Listener

* The event `\In2code\In2publishCore\Event\PublishingOfOneRecordBegan` will be fired for each record being published.
* The event `\In2code\In2publishCore\Event\RecursiveRecordPublishingEnded` will be fired once after publishing. You can leverage it to finally create your task.

Remove the task self-registration from your Services.yaml and use the anomaly instead:

```yaml
  YourVendor\YourPackage\Domain\Anomaly\MyAnomaly:
    tags:
      - name: event.listener
        identifier: 'myanomaly-collect'
        method: 'collectInfo'
        event: In2code\In2publishCore\Event\PublishingOfOneRecordBegan
      - name: event.listener
        identifier: 'myanomaly-write'
        method: 'writeTask'
        event: In2code\In2publishCore\Event\RecursiveRecordPublishingEnded
```

#### Task Class

You can now use the `'tables'` index from the Task's configuration.

```PHP
<?php

declare(strict_types=1);

namespace YourVendor\YourPackage\Domain\Task;

class MyTask extends \In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Model\Task\AbstractTask
{
    public function modifyConfiguration()
    {
    }

    protected function executeTask(): bool
    {
        foreach ($this->configuration['tables'] as $table) {
            // do something with $table on foreign
        }

        return true;
    }
}
```

Notice that this class does not register itself, since it's not the tasks responsibility. Its only responsibility is to execute steps on the foreign system based on its configuration.
