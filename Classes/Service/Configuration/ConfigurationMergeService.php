<?php
namespace In2code\In2publishCore\Service\Configuration;

use TYPO3\CMS\Core\SingletonInterface;

class ConfigurationMergeService implements SingletonInterface
{
    /**
     * @param array $original
     * @param array $additional
     * @return array
     */
    public function merge(array $original, array $additional)
    {
        $result = $original;
        foreach ($additional as $key => $value) {
            if (!is_int($key)) {
                $result[$key] = $this->getResultingValue($original, $additional, $key);
            } else {
                if (!in_array($value, $original)) {
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
            $result = $this->merge($originalValue, $additionalValue);
        } else {
            $result = $additionalValue;
        }

        return $result;
    }
}
