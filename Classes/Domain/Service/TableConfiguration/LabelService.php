<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Domain\Service\TableConfiguration;

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
use In2code\In2publishCore\Service\Configuration\TcaService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Class LabelService
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
        $this->tcaService = GeneralUtility::makeInstance(TcaService::class);
    }

    /**
     * Get label field from record
     *
     * @param RecordInterface $record
     * @param string $stagingLevel "local" or "foreign"
     * @return string
     */
    public function getLabelField($record, $stagingLevel = 'local')
    {
        $tableName = $record->getTableName();

        if ($tableName === 'sys_file_reference') {
            return sprintf(
                '%d [%d,%d]',
                $record->getPropertyBySideIdentifier($stagingLevel, 'uid'),
                $record->getPropertyBySideIdentifier($stagingLevel, 'uid_local'),
                $record->getPropertyBySideIdentifier($stagingLevel, 'uid_foreign')
            );
        }

        $fields = $this->getLabelFieldsFromTableConfiguration($tableName);
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

        $labelFields = [];
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
