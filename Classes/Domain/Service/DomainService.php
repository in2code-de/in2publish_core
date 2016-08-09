<?php
namespace In2code\In2publishCore\Domain\Service;

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Domain\Repository\CommonRepository;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use In2code\In2publishCore\Utility\DatabaseUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class DomainService
 *
 * @package In2code\In2publish\Domain\Service
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
        $this->commonRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get(
            'In2code\\In2publishCore\\Domain\\Repository\\CommonRepository',
            DatabaseUtility::buildLocalDatabaseConnection(),
            DatabaseUtility::buildForeignDatabaseConnection(),
            self::TABLE_NAME
        );
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

            case 'physical_file':
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
