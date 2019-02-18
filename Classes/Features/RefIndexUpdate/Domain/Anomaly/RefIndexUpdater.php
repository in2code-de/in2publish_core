<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Features\RefIndexUpdate\Domain\Anomaly;

/***************************************************************
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
 * Holger Krämer <post@holgerkraemer.com>
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
 ***************************************************************/

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Features\RefIndexUpdate\Domain\Model\Task\RefIndexUpdateTask;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use function is_int;

/**
 * Class RefIndexUpdater
 */
class RefIndexUpdater implements SingletonInterface
{
    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * Constructor
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
    }

    /**
     * Add task for updating sys_refindex in foreign DB
     *
     * @param string $tableName
     * @param Record $record
     */
    public function registerRefIndexUpdate($tableName, Record $record)
    {
        $uid = $record->getIdentifier();

        if (!empty($tableName) && is_int($uid) && !isset($this->configuration[$tableName][$uid])) {
            $this->configuration[$tableName][$uid] = $uid;
        }
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function writeRefIndexUpdateTask()
    {
        $this->taskRepository->add(GeneralUtility::makeInstance(RefIndexUpdateTask::class, $this->configuration));
    }
}
