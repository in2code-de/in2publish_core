<?php
namespace In2code\In2publishCore\Domain\Anomaly;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 in2code.de
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

use In2code\In2publish\Domain\Model\Record;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Utility\ArrayUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SysLogPublisher
 *
 * @package In2code\In2publish\Domain\Anomaly
 */
class SysLogPublisher
{
    /**
     * @var \In2code\In2publishCore\Domain\Repository\TaskRepository
     * @inject
     */
    protected $taskRepository;

    /**
     * @var \In2code\In2publishCore\Domain\Factory\RecordFactory
     * @inject
     */
    protected $recordFactory;

    /**
     * @var Logger
     */
    protected $logger = null;

    /**
     * @var DatabaseConnection
     */
    protected $localDatabase = null;

    /**
     * @var DatabaseConnection
     */
    protected $foreignDatabase = null;

    /**
     * @var CommonRepository
     */
    protected $commonRepository = null;

    /**
     * @var string
     */
    protected $sysLogTableName = 'sys_log';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(get_class($this));
        $this->localDatabase = DatabaseUtility::buildLocalDatabaseConnection();
        $this->foreignDatabase = DatabaseUtility::buildForeignDatabaseConnection();
        $this->commonRepository = CommonRepository::getDefaultInstance();
        $this->commonRepository->setTableName($this->sysLogTableName);
    }

    /**
     * Always publish last sys_log entry to current page
     *
     * @param string $tableName
     * @param Record $record
     * @return void
     */
    public function publishSysLog($tableName, Record $record)
    {
        if ($tableName === 'pages') {
            $sysLogRow = $this->getLastLocalSysLogProperties($record, array('uid'));
            if (!empty($sysLogRow)) {
                $this->foreignDatabase->exec_INSERTquery($this->sysLogTableName, $sysLogRow);
                $this->logger->notice(
                    'sys_log table automaticly published',
                    array('tableName' => $tableName, 'identifier' => $record->getIdentifier())
                );
            }
        }
    }

    /**
     * Get properties from last sys_log entry to current page on local system
     *
     * @param Record $record
     * @param array $removeProperties
     * @return array
     */
    protected function getLastLocalSysLogProperties(Record $record, array $removeProperties = array())
    {
        $row = $this->commonRepository->findLastPropertiesByPropertyAndTablename(
            $this->localDatabase,
            $this->sysLogTableName,
            'event_pid',
            $record->getIdentifier()
        );
        $row = ArrayUtility::removeFromArrayByKey($row, $removeProperties);
        return $row;
    }
}
