<?php
namespace In2code\In2publishCore\Utility;

class ConfigurationUtility
{
    /**
     * Merges two configuration arrays recursively
     *
     * - the value of items having an identical ALPHANUMERIC key will be REPLACED
     * - the value of items having an identical NUMERIC key will be ADDED
     *
     * @param array $original
     * @param array $additional
     * @return array
     */
    public static function mergeConfiguration(array $original, array $additional)
    {
        if (empty($additional)) {
            return $original;
        }

        $result = $original;

        foreach ($additional as $key => $value) {
            if ($value === '__UNSET') {
                unset($result[$key]);
            } elseif (!is_int($key)) {
                // Replace original value
                $result[$key] = self::getResultingValue($original, $additional, $key);
            } else {
                if (!in_array($value, $original, true)) {
                    // Add additional value
                    $result[] = self::getResultingValue($original, $additional, $key);
                }
            }
        }

        $keyOrder = array_flip(array_keys($additional));
        uksort(
            $result,
            function ($left, $right) use ($keyOrder) {

                if (!isset($keyOrder[$left])
                    || !isset($keyOrder[$right])
                    || $keyOrder[$left] === $keyOrder[$right]
                ) {
                    return 0;
                }
                return $keyOrder[$left] < $keyOrder[$right] ? -1 : 1;
            }
        );

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

        if (is_array($originalValue)
            &&
            is_array($additionalValue)
        ) {
            // Merge recursively
            $result = self::mergeConfiguration($originalValue, $additionalValue);
        } else {
            // Use additional value (to add/replace)
            $result = $additionalValue;
        }

        return $result;
    }
}
