<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Utility;

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

        if (array_key_exists('definition', $additional)) {
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

        if (array_key_exists('definition', $additional)) {
            foreach ($result['definition'] as $key => $value) {
                unset($result['definition'][$key]);
                $result['definition'][(int)$key] = $value;
            }
        }

        return $result;
    }

    protected static function overruleResultByAdditional(array $original, array $additional, $result): array
    {
        foreach ($additional as $key => $value) {
            if ($value === '__UNSET') {
                unset($result[$key]);
            } elseif (!\is_int($key)) {
                // Replace original value
                $result[$key] = self::getResultingValue($original, $additional, $key);
            } elseif (!\in_array($value, $original, true)) {
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
     * @return array|mixed|null
     */
    private static function getResultingValue(array $original, array $additional, $key)
    {
        $originalValue = array_key_exists($key, $original) ? $original[$key] : null;
        $additionalValue = array_key_exists($key, $additional) ? $additional[$key] : null;

        if (is_array($originalValue) && is_array($additionalValue)) {
            // Merge recursively
            $result = self::mergeConfiguration($originalValue, $additionalValue);
        } else {
            // Use additional value (to add/replace)
            $result = $additionalValue;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    protected static function sortResultArrayByAdditionalKeyOrder(array $result, array $original, array $additional)
    {
        $additionalKeys = array_keys($additional);
        $originalKeys = array_keys($original);
        $originalKeys = array_diff($originalKeys, $additionalKeys);
        $keyOrder = array_merge($additionalKeys, $originalKeys);
        $keyOrder = array_flip($keyOrder);

        uksort(
            $result,
            function ($left, $right) use ($keyOrder) {
                if (!isset($keyOrder[$left])
                    || !isset($keyOrder[$right])
                    || $keyOrder[$left] === $keyOrder[$right]
                ) {
                    // Be deterministic. If 0 is returned the array will be reversed
                    return 1;
                }
                return $keyOrder[$left] < $keyOrder[$right] ? -1 : 1;
            }
        );
        return $result;
    }
}
