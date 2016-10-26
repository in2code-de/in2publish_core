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

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
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
     * @var string
     */
    protected $stagingLevel;

    /**
     * @var CommonRepository
     */
    protected $commonRepository = null;

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->commonRepository = CommonRepository::getDefaultInstance(self::TABLE_NAME);
    }

    /**
     * Get domain from rootline without trailing slash
     *
     * @param Record $record
     * @param string $stagingLevel "local" or "foreign"
     * @param bool $addProtocol
     * @return string
     */
    public function getFirstDomain(Record $record, $stagingLevel = self::LEVEL_LOCAL, $addProtocol = true)
    {
        $this->stagingLevel = $stagingLevel;

        switch ($record->getTableName()) {
            case 'pages':
                $domainName = $this->getRootlineDomainFromRelatedRecords($record);
                if ($domainName === null) {
                    $domainName = $this->getDomainRecordFromDatabaseConnectionAndRootLine($record);
                }
                break;

            case 'sys_file':
                $domainName = ConfigurationUtility::getConfiguration('filePreviewDomainName.' . $this->stagingLevel);
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
     * Find first domain record from database connection
     *
     * @param Record $record
     * @return string
     */
    protected function getDomainRecordFromDatabaseConnectionAndRootLine(Record $record)
    {
        $rootline = BackendUtility::BEgetRootLine($record->getIdentifier());
        foreach ($rootline as $page) {
            $pageIdentifier = (int)$page['uid'];
            // TODO this seems to be called too often
            $domainRecords = $this->commonRepository->findByProperty('pid', $pageIdentifier);
            foreach ($domainRecords as $domainRecord) {
                /** @var Record $domainRecord */
                if (!$this->isRecordDisabled($domainRecord)) {
                    $domainProperties = ObjectAccess::getProperty($domainRecord, $this->stagingLevel . 'Properties');
                    return $domainProperties['domainName'];
                }
            }
        }
        return '';
    }

    /**
     * @param Record $record
     * @return bool
     */
    protected function isRecordDisabled(Record $record)
    {
        switch ($this->stagingLevel) {
            case self::LEVEL_FOREIGN:
                return $record->isForeignRecordDisabled();
            case self::LEVEL_LOCAL:
                return $record->isLocalRecordDisabled();
        }
        return true;
    }

    /**
     * Find first domain record of related children records
     *
     * @param Record $record
     * @return string
     */
    protected function getRootlineDomainFromRelatedRecords(Record $record)
    {
        $relatedRecords = $record->getRelatedRecords();
        $domainRecordValues = array();
        if (!empty($relatedRecords[self::TABLE_NAME])) {
            foreach ($relatedRecords[self::TABLE_NAME] as $relatedDomainRecord) {
                /** @var Record $relatedDomainRecord */
                $domainProperties = ObjectAccess::getProperty($relatedDomainRecord, $this->stagingLevel . 'Properties');
                $domainRecordValues[$domainProperties['sorting']] = $domainProperties['domainName'];
            }
        }
        $domainName = array_shift($domainRecordValues);
        if ($domainName === null && $record->getParentRecord() !== null) {
            $domainName = self::getRootlineDomainFromRelatedRecords($record->getParentRecord());
        }
        return $domainName;
    }
}
