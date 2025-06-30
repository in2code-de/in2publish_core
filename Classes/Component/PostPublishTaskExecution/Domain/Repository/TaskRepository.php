<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Repository;

/*
 * Copyright notice
 *
 * (c) 2015 in2code.de and the following authors:
 * Alex Kellner <alexander.kellner@in2code.de>,
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use DateTime;
use DateTimeImmutable;
use In2code\In2publishCore\CommonInjection\DatabaseOfForeignInjection;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Factory\TaskFactory;
use In2code\In2publishCore\Component\PostPublishTaskExecution\Domain\Model\Task\AbstractTask;

use function array_merge;

class TaskRepository
{
    use DatabaseOfForeignInjection;

    public const TASK_TABLE_NAME = 'tx_in2code_in2publish_task';
    protected TaskFactory $taskFactory;
    protected string $creationDate;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function __construct(TaskFactory $taskFactory)
    {
        $this->creationDate = (new DateTime('now'))->format('Y-m-d H:i:s');
        $this->taskFactory = $taskFactory;
    }

    public function add(AbstractTask $task): void
    {
        $this->databaseOfForeign->insert(
            self::TASK_TABLE_NAME,
            array_merge($task->toArray(), ['creation_date' => $this->creationDate]),
        );
    }

    public function update(AbstractTask $task): void
    {
        $this->databaseOfForeign->update(
            self::TASK_TABLE_NAME,
            $task->toArray(),
            ['uid' => $task->getUid()],
        );
    }

    /**
     * NULL: finds all Tasks which were not executed
     * DateTime: finds all Tasks which were executed on the given Time
     *
     * @return AbstractTask[]
     */
    public function findByExecutionBegin(DateTime|null $executionBegin = null): array
    {
        $query = $this->databaseOfForeign->createQueryBuilder();
        $query->getRestrictions()->removeAll();

        if ($executionBegin instanceof DateTime) {
            $formattedExecutionBegin = $query->createNamedParameter($executionBegin->format('Y-m-d H:i:s'));
            $predicates = $query->expr()->like('execution_begin', $formattedExecutionBegin);
        } else {
            $predicates = $query->expr()->or($query->expr()->isNull('execution_begin'), $query->expr()->like('execution_begin', $query->createNamedParameter('0000-00-00 00:00:00')));
        }

        $taskObjects = [];
        $tasksPropertiesArray = $query->select('*')
                                      ->from(self::TASK_TABLE_NAME)
                                      ->where($predicates)
                                      ->executeQuery()
                                      ->fetchAllAssociative();
        foreach ($tasksPropertiesArray as $taskProperties) {
            $taskObjects[] = $this->taskFactory->convertToObject($taskProperties);
        }
        return $taskObjects;
    }

    /**
     * Removes Tasks from the task table, which are older than a week.
     * This method only removes Tasks which were successful, so errors can
     * be investigated, even a long time after they occurred.
     */
    public function deleteObsolete(): void
    {
        $query = $this->databaseOfForeign->createQueryBuilder();
        $executionEnd = (new DateTimeImmutable('1 week ago'))->format('Y-m-d H:i:s');
        $query->delete('tx_in2code_in2publish_task')
              ->where(
                  $query->expr()->lte('execution_end', $query->createNamedParameter($executionEnd)),
              );
        $query->executeStatement();
    }
}
