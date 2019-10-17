<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Command\PublishTaskRunner;

use In2code\In2publishCore\Domain\Model\Task\AbstractTask;
use In2code\In2publishCore\Domain\Repository\TaskRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function json_encode;

class RunTasksInQueueCommand extends Command
{
    public const IDENTIFIER = 'in2publish_core:publishtasksrunner:runtasksinqueue';
    protected const DESCRIPTION = <<<'TXT'
Reads all Tasks to execute from the Database and executes them one after another.
The success of a Task is echoed to the console or scheduler backend module, including any error message of failed tasks.
NOTE: This command is used for internal operations in in2publish_core
TXT;

    protected function configure()
    {
        $this->setHidden(true)
             ->setDescription(static::DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
        $result = [];
        // Tasks which should get executed do not have an execution begin
        $tasksToExecute = $taskRepository->findByExecutionBegin(null);
        /** @var AbstractTask $task */
        foreach ($tasksToExecute as $task) {
            try {
                $success = $task->execute();
                $result[] = 'Task ' . $task->getUid() . ($success ? ' was executed successfully' : ' failed');
                $result[] = $task->getMessages();
            } catch (Throwable $e) {
                $result[] = $e->getMessage();
            }
            $taskRepository->update($task);
        }
        if (empty($result)) {
            $result[] = 'There was nothing to execute';
        }
        echo json_encode($result);
    }
}
