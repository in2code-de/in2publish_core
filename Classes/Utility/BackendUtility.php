<?php
namespace In2code\In2publishCore\Utility;

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

use TYPO3\CMS\Backend\Utility\BackendUtility as BackendUtilityCore;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class BackendUtility
 */
class BackendUtility
{
    /**
     * Get current page uid (normally from ?id=123)
     *
     * @param mixed $identifier
     * @param string $table
     * @return int
     */
    public static function getPageIdentifier($identifier = null, $table = null)
    {
        // get id from given identifier
        if ('pages' === $table && is_numeric($identifier)) {
            return (int)$identifier;
        }

        // get id from ?id=123
        if (null !== ($identifier = GeneralUtility::_GP('id'))) {
            return (int)$identifier;
        }

        // get id from ?cmd[pages][123][delete]=1
        if (null !== ($cmd = GeneralUtility::_GP('cmd'))) {
            if (isset($cmd['pages']) && is_array($cmd['pages'])) {
                foreach (array_keys($cmd['pages']) as $pid) {
                    return (int)$pid;
                }
            }
        }

        // get id from ?popViewId=123
        if (null !== ($popViewId = GeneralUtility::_GP('popViewId'))) {
            return (int)$popViewId;
        }

        // get id from ?redirect=script.php?param1=a&id=123&param2=2
        if (null !== ($redirect = GeneralUtility::_GP('redirect'))) {
            $urlParts = parse_url($redirect);
            if (!empty($urlParts['query']) && stristr($urlParts['query'], 'id=')) {
                parse_str($urlParts['query'], $parameters);
                if (!empty($parameters['id'])) {
                    return (int)$parameters['id'];
                }
            }
        }

        // get id from record ?data[tt_content][13]=foo
        if (null !== ($data = GeneralUtility::_GP('data')) && is_array($data)) {
            $table = key($data);
            $result = self::getDatabaseConnection()->exec_SELECTgetSingleRow(
                'pid',
                $table,
                'uid=' . (int)key($data[$table])
            );
            if (false !== $result && isset($result['pid'])) {
                return (int)$result['pid'];
            }
        }

        // get id from rollback ?element=tt_content:42
        if (null !== ($rollbackFields = GeneralUtility::_GP('element')) && is_string($rollbackFields)) {
            $rollbackData = explode(':', $rollbackFields);
            if (count($rollbackData) > 1) {
                $result = self::getDatabaseConnection()->exec_SELECTgetSingleRow(
                    'pid',
                    $rollbackData[0],
                    'uid=' . (int)$rollbackData[1]
                );
                if (false !== $result && isset($result['pid'])) {
                    return (int)$result['pid'];
                }
            }
        }

        return 0;
    }

    /**
     * Create an URI to edit a record
     *
     * @param string $tableName
     * @param int $identifier
     * @param bool $addReturnUrl
     * @return string
     * @todo Remove outdated URI generation for TYPO3 6.2 in upcoming major version
     */
    public static function buildEditUri($tableName, $identifier, $addReturnUrl = true)
    {
        // use new link generation in backend for TYPO3 7.2 or newer
        if (GeneralUtility::compat_version('7.2')) {
            $uriParameters = array(
                'edit' => array(
                    $tableName => array(
                        $identifier => 'edit',
                    ),
                ),
            );
            if ($addReturnUrl) {
                $uriParameters['returnUrl'] = self::getCurrentReturnUrl();
            }
            $editUri = BackendUtilityCore::getModuleUrl('record_edit', $uriParameters);
        } else {
            $editUri = FolderUtility::getSubFolderOfCurrentUrl();
            $editUri .= 'typo3/alt_doc.php?edit[' . $tableName . '][' . $identifier . ']=edit';
            if ($addReturnUrl) {
                $editUri .= '&returnUrl=' . self::getCurrentReturnUrl();
            }
        }
        return $editUri;
    }

    /**
     * Create an URI to undo a record
     *
     * @param string $tableName
     * @param int $identifier
     * @param bool $addReturnUrl
     * @return string
     * @todo Remove outdated URI generation for TYPO3 6.2 in upcoming major version
     */
    public static function buildUndoUri($tableName, $identifier, $addReturnUrl = true)
    {
        // use new link generation in backend for TYPO3 7.2 or newer
        if (GeneralUtility::compat_version('7.2')) {
            $uriParameters = array(
                'element' => $tableName . ':' . $identifier,
            );
            if ($addReturnUrl) {
                $uriParameters['returnUrl'] = self::getCurrentReturnUrl();
            }
            $undoUri = BackendUtilityCore::getModuleUrl('record_history', $uriParameters);
        } else {
            $undoUri = FolderUtility::getSubFolderOfCurrentUrl();
            $undoUri .= 'typo3/mod.php?M=record_history&element=' . $tableName . ':' . $identifier;
            $undoUri .= '&moduleToken=' . FormProtectionFactory::get()->generateToken('moduleCall', 'record_history');
            if ($addReturnUrl) {
                $undoUri .= '&returnUrl=' . self::getCurrentReturnUrl();
            }
        }
        return $undoUri;
    }

    /**
     * Get return URL from current request
     *
     * @return string
     * @todo Remove outdated edit URI for TYPO3 6.2 in upcoming major version
     */
    public static function getCurrentReturnUrl()
    {
        if (GeneralUtility::compat_version('7.2')) {
            $uri = BackendUtilityCore::getModuleUrl(
                GeneralUtility::_GET('M'),
                self::getCurrentParameters()
            );
        } else {
            $uri = self::getDeprecatedModuleUrl();
        }
        return $uri;
    }

    /**
     * Build URI to current module for TYPO3 < 7.2
     *
     * @param bool $rawUrlEncode
     * @return string
     */
    protected static function getDeprecatedModuleUrl($rawUrlEncode = true)
    {
        $uri = GeneralUtility::getIndpEnv('SCRIPT_NAME');
        $uri .= '?' . self::buildParametersStringFromParameters(self::getCurrentParameters(false));

        if ($rawUrlEncode) {
            $uri = urlencode($uri);
        }
        return $uri;
    }

    /**
     * Build parameters string for a uri
     *
     *      array(
     *          'id' => 123,
     *          'param' => 'abc'
     *      )
     *
     *     =>
     *
     *      &id=123&param=abc
     *
     * @param array $parameters
     * @return string
     */
    protected static function buildParametersStringFromParameters(array $parameters)
    {
        $parameterString = '';
        foreach ($parameters as $key => $value) {
            $parameterString .= '&' . $key . '=' . $value;
        }
        return $parameterString;
    }

    /**
     * Get all GET/POST params without module name and token
     *
     *  array(
     *      'id' => '123',
     *      'param' => 'abc'
     *  )
     *
     * @param bool $removeModuleParameters
     * @return array
     */
    protected static function getCurrentParameters($removeModuleParameters = true)
    {
        $parameters = array();
        $moduleParameterKeys = array(
            'M',
            'moduleToken',
        );
        $ignoreKeys = self::getIgnoreKeysForCurrentParameters();

        foreach ((array)GeneralUtility::_GET() as $key => $value) {
            if (in_array($key, $ignoreKeys) || (in_array($key, $moduleParameterKeys) && $removeModuleParameters)) {
                continue;
            }
            $parameters[$key] = $value;
        }
        return $parameters;
    }

    /**
     * @return array
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected static function getIgnoreKeysForCurrentParameters()
    {
        if (isset($GLOBALS['in2publish_core']['backend_utility']['ignored_keys_of_parameters'])
            && is_array($GLOBALS['in2publish_core']['backend_utility']['ignored_keys_of_parameters'])
        ) {
            return $GLOBALS['in2publish_core']['backend_utility']['ignored_keys_of_parameters'];
        }
        return array();
    }

    /**
     * @return DatabaseConnection
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
