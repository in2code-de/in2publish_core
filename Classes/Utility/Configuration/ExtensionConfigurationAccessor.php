<?php
namespace In2code\In2publishCore\Utility\Configuration;

/***************************************************************
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class ExtensionConfigurationAccessor
 */
class ExtensionConfigurationAccessor
{
    /**
     * Returns the actual extension configuration (without defaults).
     * It is advised to cache the returned value.
     *
     * @param string $extensionKey
     * @return array
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public static function getExtensionConfiguration($extensionKey = 'in2publish_core')
    {
        $extConf = [];
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey])) {
            if (is_string($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey])) {
                // default case: The extConf is stored in serialized format
                $extConfString = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey];
                $extConfArray = unserialize($extConfString);
                if (is_array($extConfArray)) {
                    $extConf = $extConfArray;
                }
            } elseif (is_array($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey])) {
                // special case: The extension configuration is already unserialized
                $extConf = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey];
            }
        }
        return $extConf;
    }
}
