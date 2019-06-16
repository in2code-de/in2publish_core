# Custom Tasks

**What is this about?**

Sometimes you like to perform custom actions, after the publication of 
records. With the help of "custom tasks", you can tell the Foreign system
to perform actions, after you have published records.

## How does it work?

Basically it works in three parts:

1. Create your custom classes (one that collects data, one that should perform an action) 
1. Collecting data: By using the signal "publishRecordRecursiveBeforePublishing" you are 
collecting data, which will be used for your task, that will be executed after publishing
1. Executing your task at the live system: By use the signal "publishRecordRecursiveEnd"
you can start executing your task at the live system and use the collected data.

## Example

### Create your classes

#### Collector Class (Anomalie)

This class will collect all data, that you will use later in your taks.
It is executed via a signal with the method "collectInfo". 

``` php
<?php
declare(strict_types=1);

use In2code\In2publishCore\Domain\Model\RecordInterface;
use YourNameSpace\In2publishCore\Domain\Model\Task;

class Anomaly implements \TYPO3\CMS\Core\SingletonInterface
{
    protected $data = [];

    public function collectInfo(string $tableName, RecordInterface $record)
    {
        $this->data[$tableName] = true;
    }

    public function writeTask()
    {
        $taskRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \In2code\In2publishCore\Domain\Repository\TaskRepository::class
        );
        $taskConfig = ['tables' => $this->data];

        $taskRepository->add(new Task($taskConfig));
    }
}
```

#### Task Class

``` php

<?php
declare(strict_types=1);

class Task extends \In2code\In2publishCore\Domain\Model\Task\AbstractTask
{
    public function modifyConfiguration()
    {
    }

    protected function executeTask(): bool
    {
        foreach ($this->configuration['tables'] as $table) {
            // do something with table
        }
    }
}

```



#### Register signals

Add the two sigales 'publishRecordRecursiveBeforePublishing' and 'publishRecordRecursiveEnd' 
to your ext_tables.php.

* publishRecordRecursiveBeforePublishing will collect the data
* publishRecordRecursiveEnd will create a task at the live system, which will be executed

``` php

<?php
$ssDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
$ssDispatcher->connect(
    \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
    'publishRecordRecursiveBeforePublishing',
    \Anomaly::class,
    'collectInfo'
);
$ssDispatcher->connect(
    \In2code\In2publishCore\Domain\Repository\CommonRepository::class,
    'publishRecordRecursiveEnd',
    \Anomaly::class,
    'writeTask'
);
```
