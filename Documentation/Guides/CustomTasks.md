# Custom Tasks

**Publishing Tasks** are pieces of code that are executed after each deployment.
They are different from other executed code by the fact that the context of the execution is on foreign, hence you access the foreign database, access foreign's FAL, and run with foreign's configuration.
With the help of "custom tasks" you can tell the foreign system to perform actions after you have published records.

## How does it work?

All you need to begin with is a `Task`, which registers itself for execution.

**MyTask.php**

```PHP
<?php
declare(strict_types=1);

class MyTask extends \In2code\In2publishCore\Domain\Model\Task\AbstractTask
{
    public function modifyConfiguration()
    {
    }

    protected function executeTask(): bool
    {
        // Code to execute on foreign
    }

    public function register()
    {
        $taskRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \In2code\In2publishCore\Domain\Repository\TaskRepository::class
        );
        $taskRepository->add($this);
    }
}
```

**ext_tables.php**
```PHP
<?php
$dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$dispatcher->connect('SignalClassName', 'signalName', \MyTask::class, 'register');
```

When the signal `SignalClassName::signalName` is fired `MyTask::register` will be called and register itself for execution.
BTW: This is not a good class design. The responsibility of the Task is to execute code on foreign, not to register itself. You should use an anomaly for that (described later on).

**Attention: You can register Tasks multiple times.
If you use a signal that is fired for each published record you might end up with thousands of tasks registered during the publishing process.
These tasks will be executed on foreign and can temporarily adversely affect performance and make the publishing process very slow.**

Signals that are suited for task registration because they are fired once are:

* `\In2code\In2publishCore\Controller\RecordController::publishRecord`
* `\In2code\In2publishCore\Domain\Repository\CommonRepository::publishRecordRecursiveBegin`
* `\In2code\In2publishCore\Domain\Repository\CommonRepository::publishRecordRecursiveEnd`

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
1. (deprecated) **Modify the configuration** in the task before it is written to the database.

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
use In2code\In2publishCore\Domain\Repository\TaskRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use YourVendor\YourPackage\Domain\Task\MyTask;

class MyAnomaly implements \TYPO3\CMS\Core\SingletonInterface
{
    protected $data = [];

    public function collectInfo(string $tableName, RecordInterface $record)
    {
        // Since the MyAnomaly is a singleton the $data array will grow with each new table.
        // Slot classes that are not Singletons will be created newly for each signal dispatch and therefore can't collect data.
        $this->data[$tableName] = true;
    }

    public function writeTask()
    {
        $taskRepository = GeneralUtility::makeInstance(
            TaskRepository::class
        );
        // Provide the task config in a format the task can use it without transformation.
        // Keep the configuration as small as possible. You can set about 4 GB but you REALLY shouldn't!
        $taskConfig = ['tables' => $this->data];

        $task = GeneralUtility::makeInstance(MyTask::class, $taskConfig);
        $taskRepository->add($task);
    }
}
```

#### Signal Slot Dispatching

Wire your anomaly to the two signals `publishRecordRecursiveBeforePublishing` and `publishRecordRecursiveEnd` in your ext_tables.php.

* The signal `publishRecordRecursiveBeforePublishing` will be fired for each record being published. Your `MyAnomaly::collectInfo` method signature depends on the signal arguments.
* The signal `publishRecordRecursiveEnd` will be fired once after publishing. You can leverage it to finally create your task.

```PHP
<?php
$ssDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$ssDispatcher->connect(
    \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
    'publishRecordRecursiveBeforePublishing',
    \MyAnomaly::class,
    'collectInfo'
);
$ssDispatcher->connect(
    \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
    'publishRecordRecursiveEnd',
    \MyAnomaly::class,
    'writeTask'
);
```

#### Task Class

```PHP
<?php
declare(strict_types=1);
namespace YourVendor\YourPackage\Domain\Task;

class MyTask extends \In2code\In2publishCore\Domain\Model\Task\AbstractTask
{
    public function modifyConfiguration()
    {
    }

    protected function executeTask(): bool
    {
        foreach ($this->configuration['tables'] as $table) {
            // do something with $table
        }
    }
}
```

Notice that this class does not register itself, since it's not the tasks responsibility. Its only responsibility is to execute steps on the foreign system based on its configuration.
