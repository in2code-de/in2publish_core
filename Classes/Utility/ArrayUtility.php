<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Utility;

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

use TYPO3\CMS\Core\Utility\MathUtility;

class ArrayUtility
{
    public static function removeFromArrayByKey(array $array, array $keysToRemove = []): array
    {
        foreach ($keysToRemove as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    /**
     * Normalizes an array. Values which are equal to false or true will be
     * converted into booleans, integer like strings into integer values
     * and empty values will be removed from the array
     */
    public static function normalizeArray(array $array): array
    {
        foreach ($array as $key => &$value) {
            switch (gettype($value)) {
                case 'array':
                    $value = static::normalizeArray($value);
                    if (empty($value)) {
                        unset($array[$key]);
                    }
                    break;
                case 'string':
                    $value = self::autoCastString($array, $value, $key);
                    break;
                case 'NULL':
                    unset($array[$key]);
                    break;
                default:
            }
        }
        return $array;
    }

    /**
     * @param array $array
     * @param string $path
     * @return mixed
     */
    public static function getValueByPath(array &$array, $path)
    {
        if (\is_string($path)) {
            $path = explode('.', $path);
        } elseif (!\is_array($path)) {
            throw new \InvalidArgumentException(
                'getValueByPath() expects $path to be string or array, "' . \gettype($path) . '" given.',
                1495098452
            );
        }
        $key = array_shift($path);
        if (isset($array[$key])) {
            if (!empty($path)) {
                return \is_array($array[$key]) ? static::getValueByPath($array[$key], $path) : null;
            }
            return $array[$key];
        } else {
            return null;
        }
    }

    /**
     * @param array $array
     * @param $value
     * @param $key
     *
     * @return bool|int
     */
    protected static function autoCastString(array &$array, $value, $key)
    {
        if (strtolower($value) === 'true') {
            $value = true;
        } elseif (strtolower($value) === 'false') {
            $value = false;
        } elseif (MathUtility::canBeInterpretedAsInteger($value)) {
            $value = (int)$value;
        } elseif (\strlen($value) === 0 || strtolower($value) === 'null') {
            unset($array[$key]);
        }
        return $value;
    }
}
