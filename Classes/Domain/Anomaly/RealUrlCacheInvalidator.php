<?php
namespace In2code\In2publishCore\Domain\Anomaly;

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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Model\Task\RealUrlTask;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RealUrlCacheInvalidator
 *
 * @package In2code\In2publish\Domain\Anomaly
 */
class RealUrlCacheInvalidator
{
    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var \In2code\In2publishCore\Domain\Repository\TaskRepository
     * @inject
     */
    protected $taskRepository;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
    }

    /**
     * @param $tableName
     * @param Record $record
     * @return void
     */
    public function registerClearRealUrlCacheTask($tableName, Record $record)
    {
        if ($tableName === 'pages') {
            if (!in_array(
                $record->getLocalProperty('doktype'),
                ConfigurationUtility::getConfiguration('tasks.realUrl.excludedDokTypes')
            )
            ) {
                if (ExtensionManagementUtility::isLoaded('realurl')) {
                    $realUrlTask = new RealUrlTask(array('record' => $record));
                    $this->taskRepository->add($realUrlTask);
                }
            }
        }
    }
}
