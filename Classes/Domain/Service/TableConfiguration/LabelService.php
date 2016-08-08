<?php
namespace In2code\In2publishCore\Domain\Service\TableConfiguration;

use In2code\In2publishCore\Domain\Model\Record;
use In2code\In2publishCore\Service\Configuration\TcaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class LabelService
 * @package In2code\In2publish\Domain\Service\TableConfiguration
 */
class LabelService
{
    /**
     * @var string
     */
    protected $emptyFieldValue = '---';

    /**
     * @var TcaService
     */
    protected $tcaService = null;

    /**
     * LabelService constructor.
     */
    public function __construct()
    {
        $this->tcaService = GeneralUtility::makeInstance('In2code\\In2publishCore\\Service\\Configuration\\TcaService');
    }

    /**
     * Get label field from record
     *
     * @param Record $record
     * @param string $stagingLevel "local" or "foreign"
     * @return string
     */
    public function getLabelField($record, $stagingLevel = 'local')
    {
        $fields = $this->getLabelFieldsFromTableConfiguration($record->getTableName());
        foreach ($fields as $field) {
            $recordProperties = ObjectAccess::getProperty($record, $stagingLevel . 'Properties');
            if (!empty($recordProperties[$field])) {
                return $recordProperties[$field];
            }
        }
        return $this->emptyFieldValue;
    }

    /**
     * Get label fields from a table definition
     *
     * @param string $tableName
     * @return array
     */
    protected function getLabelFieldsFromTableConfiguration($tableName)
    {
        $labelField = $this->tcaService->getLabelFieldFromTable($tableName);
        $labelAltField = $this->tcaService->getLabelAltFieldFromTable($tableName);

        $labelFields = array();
        if (!empty($labelField)) {
            $labelFields[] = $labelField;
        }
        if (!empty($labelAltField)) {
            $labelFields = array_merge(
                $labelFields,
                GeneralUtility::trimExplode(',', $labelAltField, true)
            );
        }
        return array_unique($labelFields);
    }
}
