<?php
declare(strict_types=1);
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

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Model\RecordInterface;
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
    private $configContainer;

    /**
     * DomainService constructor.
     */
    public function __construct()
    {
        $this->configContainer = GeneralUtility::makeInstance(ConfigContainer::class);
    }

    /**
     * @param RecordInterface $record
     * @param string $stagingLevel
     * @param bool $addProtocol
     * @return mixed|string
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
                $domainName = $this->configContainer->get('filePreviewDomainName.' . $stagingLevel);
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
     * @return string|null
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
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function getDomainFromPageIdentifier($identifier, $stagingLevel): string
    {
        $rootLine = BackendUtility::BEgetRootLine($identifier);
        foreach ($rootLine as $page) {
            $connection = DatabaseUtility::buildDatabaseConnectionForSide($stagingLevel);
            if (null === $connection) {
                // Error: not connected
                return '';
            }
            $query = DatabaseUtility::buildLocalDatabaseConnection()->createQueryBuilder();
            $query->getRestrictions()->removeAll();
            $domainRecord = $query->select('domainName')
                                  ->from(static::TABLE_NAME)
                                  ->where($query->expr()->eq('pid', (int)$page['uid']))
                                  ->andWhere('hidden', 0)
                                  ->orderBy('sorting', 'ASC')
                                  ->setMaxResults(1)
                                  ->execute()
                                  ->fetch(\PDO::FETCH_ASSOC);
            if (isset($domainRecord['domainName'])) {
                return $domainRecord['domainName'];
            }
        }
        return '';
    }
}
