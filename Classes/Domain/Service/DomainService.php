<?php
namespace In2code\In2publishCore\Domain\Service;

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

use In2code\In2publishCore\Domain\Model\RecordInterface;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class DomainService
 */
class DomainService
{
    const TABLE_NAME = 'sys_domain';
    const LEVEL_LOCAL = 'local';
    const LEVEL_FOREIGN = 'foreign';

    /**
     * Get domain from root line without trailing slash
     *
     * @param RecordInterface $record
     * @param string $stagingLevel "local" or "foreign"
     * @param bool $addProtocol
     * @return string
     */
    public function getFirstDomain(RecordInterface $record, $stagingLevel = self::LEVEL_LOCAL, $addProtocol = true)
    {
        switch ($record->getTableName()) {
            case 'pages':
                $domainName = $this->getFirstDomainInRootLineFromRelatedRecords($record, $stagingLevel);
                if ($domainName === null) {
                    $domainName = $this->getDomainFromPageIdentifier($record->getIdentifier(), $stagingLevel);
                }
                break;

            case 'sys_file':
                $domainName = ConfigurationUtility::getConfiguration('filePreviewDomainName.' . $stagingLevel);
                break;

            default:
                $domainName = GeneralUtility::getIndpEnv('HTTP_HOST');
        }

        if ($addProtocol) {
            $domainName = '//' . $domainName;
        }

        return $domainName;
    }

    /**
     * Find first domain record of related children records
     *
     * @param RecordInterface $record
     * @param string $stagingLevel
     * @return string
     */
    protected function getFirstDomainInRootLineFromRelatedRecords(RecordInterface $record, $stagingLevel)
    {
        $relatedRecords = $record->getRelatedRecords();
        $domainRecordValues = [];
        if (!empty($relatedRecords[static::TABLE_NAME])) {
            foreach ($relatedRecords[static::TABLE_NAME] as $relatedDomainRecord) {
                /** @var RecordInterface $relatedDomainRecord */
                $domainProperties = ObjectAccess::getProperty($relatedDomainRecord, $stagingLevel . 'Properties');
                $domainRecordValues[$domainProperties['sorting']] = $domainProperties['domainName'];
            }
        }
        $domainName = array_shift($domainRecordValues);
        if ($domainName === null && $record->getParentRecord() !== null) {
            $domainName = static::getFirstDomainInRootLineFromRelatedRecords($record->getParentRecord(), $stagingLevel);
        }
        return $domainName;
    }

    /**
     * @param int $identifier UID of a pages record
     * @param string $stagingLevel
     * @return string
     */
    public function getDomainFromPageIdentifier($identifier, $stagingLevel)
    {
        $rootLine = BackendUtility::BEgetRootLine($identifier);
        foreach ($rootLine as $page) {
            $databaseConnection = DatabaseUtility::buildDatabaseConnectionForSide($stagingLevel);
            if (null === $databaseConnection) {
                // Error: not connected
                return '';
            }
            $domainRecord = $databaseConnection->exec_SELECTgetSingleRow(
                'domainName',
                static::TABLE_NAME,
                'pid=' . (int)$page['uid'] . ' AND hidden=0',
                '',
                'sorting'
            );
            if (isset($domainRecord['domainName'])) {
                return $domainRecord['domainName'];
            }
        }
        return '';
    }
}
