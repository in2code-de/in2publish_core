<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Utility;

/*
 * Copyright notice
 *
 * (c) 2018 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
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
 */

use function array_diff;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function in_array;
use function is_array;
use function is_int;
use function uksort;

class ConfigurationUtility
{
    /**
     * Merges two configuration arrays recursively
     *
     * - the value of items having an identical ALPHANUMERIC key will be REPLACED
     * - the value of items having an identical NUMERIC key will be ADDED
     */
    public static function mergeConfiguration(array $original, array $additional): array
    {
        if (empty($additional)) {
            return $original;
        }

        if (array_key_exists('definition', $additional) && is_array($additional['definition'])) {
            foreach ($additional['definition'] as $key => $value) {
                unset($additional['definition'][$key]);
                $additional['definition']['0' . $key] = $value;
            }
            foreach ($original['definition'] as $key => $value) {
                unset($original['definition'][$key]);
                $original['definition']['0' . $key] = $value;
            }
        }

        $result = $original;
        $result = self::overruleResultByAdditional($original, $additional, $result);
        $result = self::sortResultArrayByAdditionalKeyOrder($result, $original, $additional);

        if (array_key_exists('definition', $additional) && is_array($additional['definition'])) {
            foreach ($result['definition'] as $key => $value) {
                unset($result['definition'][$key]);
                $result['definition'][(int)$key] = $value;
            }
        }

        return $result;
    }

    protected static function overruleResultByAdditional(array $original, array $additional, array $result): array
    {
        foreach ($additional as $key => $value) {
            if ($value === '__UNSET') {
                unset($result[$key]);
            } elseif (!is_int($key)) {
                // Replace original value
                $result[$key] = self::getResultingValue($original, $additional, $key);
            } elseif (!in_array($value, $original, true)) {
                // Add additional value
                $result[] = self::getResultingValue($original, $additional, $key);
            }
        }
        return $result;
    }

    /**
     * @param array $original
     * @param array $additional
     * @param mixed $key
     *
     * @return array|mixed|null
     */
    private static function getResultingValue(array $original, array $additional, $key)
    {
        $originalValue = $original[$key] ?? null;
        $additionalValue = $additional[$key] ?? null;

        if (is_array($originalValue) && is_array($additionalValue)) {
            // Merge recursively
            $result = self::mergeConfiguration($originalValue, $additionalValue);
        } else {
            // Use additional value (to add/replace)
            $result = $additionalValue;
        }

        return $result;
    }

    protected static function sortResultArrayByAdditionalKeyOrder(
        array $result,
        array $original,
        array $additional
    ): array {
        $additionalKeys = array_keys($additional);
        $originalKeys = array_keys($original);
        $originalKeys = array_diff($originalKeys, $additionalKeys);
        $keyOrder = array_merge($additionalKeys, $originalKeys);
        $keyOrder = array_flip($keyOrder);

        uksort(
            $result,
            static function ($left, $right) use ($keyOrder) {
                if (!isset($keyOrder[$left], $keyOrder[$right]) || $keyOrder[$left] === $keyOrder[$right]) {
                    // Be deterministic. If 0 is returned the array will be reversed
                    return 1;
                }
                return $keyOrder[$left] < $keyOrder[$right] ? -1 : 1;
            },
        );
        return $result;
    }
}
