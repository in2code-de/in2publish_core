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
    public function mergeConfiguration(array $original, array $additional)
    {
        $result = array_merge($original, $additional);

        return $result;
    }
}
