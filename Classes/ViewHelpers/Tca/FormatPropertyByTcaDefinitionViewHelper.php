<?php
namespace In2code\In2publishCore\ViewHelpers\Tca;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>,
 *  Oliver Eglseder <oliver.eglseder@in2code.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class FormatPropertyByTcaDefinitionViewHelper
 */
class FormatPropertyByTcaDefinitionViewHelper extends AbstractViewHelper
{
    /**
     * @var array
     */
    protected $tableConfiguration = [];

    /**
     *
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('fieldName', 'string', 'The field name to get the localized label from', true);
        $this->registerArgument('tableName', 'string', 'The table name in which the field can be found', true);
    }

    /**
     * Get formatted output by TCA definition
     *
     * @return string
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public function render()
    {
        $fieldName = $this->arguments['fieldName'];
        $tableName = $this->arguments['tableName'];

        $this->tableConfiguration = $GLOBALS['TCA'][$tableName]['columns'][$fieldName];
        $value = trim($this->renderChildren());

        if (empty($this->tableConfiguration['config']['type'])) {
            return $value;
        }

        switch ($this->tableConfiguration['config']['type']) {
            case 'input':
                $this->changeValueForTypeInput($value);
                break;
            case 'text':
                $this->changeValueForTypeText($value);
                break;
            default:
        }

        $this->setValueToNoValue($value);

        return $value;
    }

    /**
     * @param $value
     */
    protected function changeValueForTypeInput(&$value)
    {
        if (GeneralUtility::inList($this->tableConfiguration['config']['eval'], 'datetime')
            || GeneralUtility::inList($this->tableConfiguration['config']['eval'], 'date')
        ) {
            if ($value !== '0') {
                $value = strftime('%d.%m.%Y', $value);
            }
        }
        if (GeneralUtility::inList($this->tableConfiguration['config']['eval'], 'password')) {
            $value = '*******';
        }
    }

    /**
     * @param $value
     */
    protected function changeValueForTypeText(&$value)
    {
        $value = nl2br($value);
    }

    /**
     * @param $value
     */
    protected function setValueToNoValue(&$value)
    {
        if (empty($value)) {
            $value = '-';
        }
    }
}
