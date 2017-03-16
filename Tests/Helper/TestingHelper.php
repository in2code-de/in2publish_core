<?php
namespace In2code\In2publishCore\Tests\Helper;

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

use In2code\In2publishCore\Service\Context\ContextService;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class TestingHelper
 */
class TestingHelper
{
    /**
     * @param mixed $value
     * @param string $extensionKey
     * @param bool $serialize
     * @SuppressWarnings("PHPMD.Superglobals")
     */
    public static function setExtConf($value, $extensionKey = 'in2publish_core', $serialize = true)
    {
        if (true === $serialize && is_array($value)) {
            $value = serialize($value);
        }
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey] = $value;
    }

    /**
     * @param string|null $value
     */
    public static function setIn2publishContext($value)
    {
        if (null === $value) {
            putenv(ContextService::ENV_VAR_NAME);
        } else {
            putenv(ContextService::ENV_VAR_NAME . '=' . $value);
        }
    }

    /**
     * @param string|null $value
     */
    public static function setRedirectedIn2publishContext($value)
    {
        if (null === $value) {
            putenv(ContextService::REDIRECT_ENV_VAR_NAME);
        } else {
            putenv(ContextService::REDIRECT_ENV_VAR_NAME . '=' . $value);
        }
    }

    /**
     * Unsets any supported in2publish context environment variable
     */
    public static function clearIn2publishContext()
    {
        static::setIn2publishContext(null);
        static::setRedirectedIn2publishContext(null);
    }

    /**
     * @param string|null $value
     */
    public static function setApplicationContext($value)
    {
        if (null === $value) {
            putenv('TYPO3_CONTEXT');
            $applicationContext = new ApplicationContext('Production');
        } else {
            putenv('TYPO3_CONTEXT=' . $value);
            $applicationContext = new ApplicationContext($value);
        }
        self::setStaticProperty(GeneralUtility::class, 'applicationContext', $applicationContext);
    }

    /**
     * @param string $class
     * @param string $name
     * @param mixed $value
     */
    public static function setStaticProperty($class, $name, $value)
    {
        $reflectionProperty = new \ReflectionProperty($class, $name);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($value);
    }
}
