<?php
namespace In2code\In2publishCore\ViewHelpers\Uri;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class PreviewAnyRecordViewHelper
 *
 * @package In2code\In2publish\ViewHelpers\Uri
 */
class PreviewAnyRecordViewHelper extends AbstractViewHelper
{
    /**
     * Build uri for any record preview on [removed] and
     * respect settings of Page TSConfig TCEMAIN.preview
     *
     * @param int $identifier
     * @param string $tableName
     * @return false|string false if not configuration found, otherwise URI
     */
    public function render($identifier, $tableName)
    {
        if ($this->isPreviewTsConfigExisting($tableName)) {
            $pageTsConfig = BackendUtility::getPagesTSconfig($this->getCurrentPageIdentifier());
            $configuration = $pageTsConfig['TCEMAIN.']['preview.'][$tableName . '.'];
            $uri = 'index.php?id=' . (int)$configuration['previewPageId'];
            $uri .= '&' . $configuration['fieldToParameterMap.']['uid'] . '=' . $identifier;
            $uri = $this->buildAdditionalParamsString($configuration, $uri);
            return $uri;
        }
        return false;
    }

    /**
     * Check if there is a Page TSConfig in TCEMAIN.preview for given Tablename
     *
     * @param string $tableName
     * @return bool
     */
    protected function isPreviewTsConfigExisting($tableName)
    {
        $pageTsConfig = BackendUtility::getPagesTSconfig($this->getCurrentPageIdentifier());
        return !empty($pageTsConfig['TCEMAIN.']['preview.'][$tableName . '.']);
    }

    /**
     * Get current PID of the chosen page - normally $_GET['id']
     *
     * @return int
     */
    protected function getCurrentPageIdentifier()
    {
        return (int)GeneralUtility::_GP('id');
    }

    /**
     * Build parameters string with additional static parameters
     *  from configuration like:
     *      TCEMAIN.preview {
     *          table {
     *              additionalGetParameters {
     *                  tx_news_pi1.controller = News
     *                  tx_news_pi1.action = detail
     *              }
     *          }
     *      }
     *
     * @param array $configuration
     * @param string $uri
     * @return string
     */
    protected function buildAdditionalParamsString($configuration, $uri)
    {
        $additionalConfig = (array)$configuration['additionalGetParameters.'];
        foreach ($additionalConfig as $additionalKey => $additionalValue) {
            if (!is_array($additionalValue)) {
                $uri .= '&' . $additionalKey . '=' . $additionalValue;
            } else {
                foreach ((array)$additionalConfig[$additionalKey] as $additionalKey2 => $additionalValue2) {
                    if (!is_array($additionalKey2)) {
                        $uri .= '&' . self::removeLastDot($additionalKey) . '[' . $additionalKey2 . ']='
                                . $additionalValue2;
                    }
                }
            }
        }
        return $uri;
    }

    /**
     * Remove last . of a string
     *
     * @param $string
     * @return string
     */
    protected static function removeLastDot($string)
    {
        if (substr($string, -1) === '.') {
            $string = substr($string, 0, -1);
        }
        return $string;
    }
}
