<?php
namespace In2code\In2publishCore\Service\Configuration;

use TYPO3\CMS\Core\SingletonInterface;

class ConfigurationService implements SingletonInterface
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
    public function mergeConfiguration(array $original, array $additional)
    {
        $result = $original;

        foreach ($additional as $key => $value) {
            if (!is_int($key)) {
                // Replace original value
                $result[$key] = $this->getResultingValue($original, $additional, $key);
            } else {
                if (!in_array($value, $original, true)) {
                    // Add additional value
                    $result[] = $this->getResultingValue($original, $additional, $key);
                }
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
    private function getResultingValue(array $original, array $additional, $key)
    {
        $originalValue = array_key_exists($key, $original) ? $original[$key] : null;
        $additionalValue = array_key_exists($key, $additional) ? $additional[$key] : null;

        if (
            is_array($originalValue)
            &&
            is_array($additionalValue)
        ) {
            // Merge recursively
            $result = $this->mergeConfiguration($originalValue, $additionalValue);
        } else {
            // Use additional value (to add/replace)
            $result = $additionalValue;
        }

        return $result;
    }
}
