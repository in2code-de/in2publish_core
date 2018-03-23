<?php
namespace In2code\In2publishCore\Features\RealUrlSupport\Domain\Anomaly;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Domain\Repository\TaskRepository;
use In2code\In2publishCore\Features\RealUrlSupport\Domain\Model\Task\RealUrlTask;
use In2code\In2publishCore\Features\RealUrlSupport\Domain\Model\Task\RealUrlUpdateTask;
use In2code\In2publishCore\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RealUrlCacheInvalidator
 */
class RealUrlCacheInvalidator
{
    /**
     * @var bool
     */
    protected $updateData = true;

    /**
     * @var TaskRepository
     */
    protected $taskRepository;

    /**
     * @var array
     */
    protected $excludedDokTypes = [];

    /**
     * Constructor
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function __construct()
    {
        $this->updateData = version_compare(ExtensionUtility::getExtensionVersion('realurl'), '2.1.0') >= 0;
        $this->taskRepository = GeneralUtility::makeInstance(TaskRepository::class);
        $this->excludedDokTypes = GeneralUtility::makeInstance(ConfigContainer::class)
                                                ->get('tasks.realUrl.excludedDokTypes');
    }

    /**
     * @param string $tableName
     * @param RecordInterface $record
     * @return void
     */
    public function registerClearRealUrlCacheTask($tableName, RecordInterface $record)
    {
        if ($record->isChanged() && in_array($tableName, ['pages', 'pages_language_overlay'])) {
            if (!in_array($record->getLocalProperty('doktype'), $this->excludedDokTypes)) {
                if ($this->updateData) {
                    $realUrlTask = new RealUrlUpdateTask(['record' => $record]);
                } else {
                    $realUrlTask = new RealUrlTask(['record' => $record]);
                }
                $this->taskRepository->add($realUrlTask);
            }
        }
    }
}
