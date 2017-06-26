<?php
namespace In2code\In2publishCore\Testing\Tests\Configuration;

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

use In2code\In2publishCore\Testing\Data\ConfigurationDefinitionProvider;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ConfigurationFormatTest
 */
class ConfigurationFormatTest implements TestCaseInterface
{
    /**
     * @var ConfigurationDefinitionProvider
     */
    protected $definitionProvider = null;

    /**
     * ConfigurationFormatTest constructor.
     */
    public function __construct()
    {
        $this->definitionProvider = GeneralUtility::makeInstance(
            ConfigurationDefinitionProvider::class
        );
    }

    /**
     * @return TestResult
     */
    public function run()
    {
        $expected = $this->definitionProvider->getConfigurationDefinition();
        $actual = ConfigurationUtility::getConfiguration();

        $superfluousKeys = $this->identifySuperfluousKeys($expected, $actual);

        if (!empty($superfluousKeys)) {
            return new TestResult(
                'configuration.superfluous_keys',
                TestResult::ERROR,
                array_merge(array('configuration.keys_superfluous'), $superfluousKeys)
            );
        }

        $missingKeys = $this->identifyMissingKeys($expected, $actual);

        if (!empty($missingKeys)) {
            return new TestResult(
                'configuration.missing_keys',
                TestResult::ERROR,
                array_merge(array('configuration.keys_missing'), $missingKeys)
            );
        }

        $mismatchingKeys = $this->identifyMismatchingKeys($expected, $actual);

        if (!empty($mismatchingKeys)) {
            return new TestResult(
                'configuration.mismatching_keys',
                TestResult::ERROR,
                array_merge(array('configuration.keys_mismatching'), $mismatchingKeys)
            );
        }

        return new TestResult('configuration.format_okay');
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        return array(
            ConfigurationIsAvailableTest::class,
        );
    }

    /**
     * @param array $expected
     * @param array $actual
     * @param array $path
     * @return array
     */
    protected function identifySuperfluousKeys(array $expected, array $actual, array $path = array())
    {
        $missingKeys = array();

        foreach ($actual as $actualKey => $actualValue) {
            array_push($path, $actualKey);
            if (!array_key_exists($actualKey, $expected)) {
                $valid = false;
                foreach (array_keys($expected) as $expectedKey) {
                    if (strpos($expectedKey, '*') === 0) {
                        $valid = true;
                        break;
                    }
                }
                if (!$valid) {
                    if (is_string($actualValue) && !empty($actualValue)) {
                        $missingKeys[] = implode('.', $path) . ' (VALUE: ' . $actualValue . ')';
                    } else {
                        $missingKeys[] = implode('.', $path);
                    }
                }
            } else {
                if (is_array($actualValue)) {
                    $missingKeys = array_merge(
                        $missingKeys,
                        $this->identifySuperfluousKeys($expected[$actualKey], $actualValue, $path)
                    );
                }
            }
            array_pop($path);
        }

        return $missingKeys;
    }

    /**
     * @param array $expected
     * @param array $actual
     * @param array $path
     * @return array
     */
    protected function identifyMissingKeys(array $expected, array $actual, array $path = array())
    {
        $missingKeys = array();

        foreach ($expected as $requiredKey => $requiredType) {
            array_push($path, $requiredKey);
            if (false !== strpos($requiredKey, '*')) {
                if (is_array($requiredType)) {
                    if (is_array($requiredType)) {
                        foreach ($actual as $actualValue) {
                            if ((is_array($actualValue))) {
                                $missingKeys = array_merge(
                                    $missingKeys,
                                    $this->identifyMissingKeys(
                                        $requiredType,
                                        $actualValue,
                                        $path
                                    )
                                );
                            } else {
                                foreach (array_keys($requiredType) as $requiredSubKey) {
                                    if (0 !== strpos($requiredSubKey, '*')) {
                                        array_push($path, $requiredSubKey);
                                        $missingKeys[] = implode('.', $path);
                                        array_pop($path);
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                if (!array_key_exists($requiredKey, $actual)) {
                    $missingKeys[] = implode('.', $path);
                } else {
                    if (is_array($requiredType)) {
                        if (is_array($actual[$requiredKey])) {
                            $missingKeys = array_merge(
                                $missingKeys,
                                $this->identifyMissingKeys(
                                    $requiredType,
                                    $actual[$requiredKey],
                                    $path
                                )
                            );
                        }
                    }
                }
            }
            array_pop($path);
        }

        return $missingKeys;
    }

    /**
     * @param array $expected
     * @param array $actual
     * @param array $path
     * @return array
     */
    protected function identifyMismatchingKeys(array $expected, array $actual, array $path = array())
    {
        $mismatchingKeys = array();

        foreach ($expected as $requiredKey => $requiredType) {
            if (false !== strpos($requiredKey, '*')) {
                $requiredKeyType = substr($requiredKey, 2);
                if ($requiredKeyType !== false) {
                    foreach (array_keys((array)$actual) as $actualKey) {
                        array_push($path, $actualKey);
                        if (!$this->mixedMatchesType($requiredKeyType, $actualKey)) {
                            $mismatchingKeys[] = $this->getTypeMismatchString(
                                $path,
                                $requiredKeyType,
                                gettype($actualKey)
                            );
                        }
                        array_pop($path);
                    }
                }

                foreach (array_keys((array)$actual) as $actualKey) {
                    array_push($path, $actualKey);
                    if (!empty($actual[$actualKey])) {
                        $actualType = gettype($actual[$actualKey]);
                        if (is_array($requiredType)) {
                            $mismatchingKeys = array_merge(
                                $mismatchingKeys,
                                $this->identifyMismatchingKeys(
                                    $requiredType,
                                    $actual[$actualKey],
                                    $path
                                )
                            );
                        } else {
                            if (!$this->typeMatchesType($requiredType, $actualType)) {
                                $mismatchingKeys[] = $this->getTypeMismatchString(
                                    $path,
                                    $requiredType,
                                    $actualType,
                                    true
                                );
                            }
                        }
                    }
                    array_pop($path);
                }
            } else {
                array_push($path, $requiredKey);
                if (is_array($requiredType)) {
                    if (is_array($actual[$requiredKey])) {
                        $mismatchingKeys = array_merge(
                            $mismatchingKeys,
                            $this->identifyMismatchingKeys(
                                $requiredType,
                                $actual[$requiredKey],
                                $path
                            )
                        );
                    } else {
                        $mismatchingKeys[] = $this->getTypeMismatchString(
                            $path,
                            $requiredType,
                            $actual[$requiredKey]
                        );
                    }
                } else {
                    if (!$this->mixedMatchesType($requiredType, $actual[$requiredKey])) {
                        $mismatchingKeys[] = $this->getTypeMismatchString(
                            $path,
                            $requiredType,
                            gettype($actual[$requiredKey])
                        );
                    }
                }
                array_pop($path);
            }
        }

        return $mismatchingKeys;
    }

    /**
     * @param array $path
     * @param mixed $expected
     * @param mixed $actual
     * @param bool $value
     * @return string
     */
    protected function getTypeMismatchString(array $path, $expected, $actual, $value = false)
    {
        if ((!is_string($expected))) {
            $expected = gettype($expected);
        }
        if (!is_string($actual)) {
            $actual = gettype($actual);
        }
        return implode('.', $path)
               . ($value ? '.VALUE' : '')
               . ' (Expected: ' . $expected
               . '; Actual: ' . $actual . ')';
    }

    /**
     * @param string $expected
     * @param mixed $actual
     * @return bool
     */
    protected function mixedMatchesType($expected, $actual)
    {
        return $this->typeMatchesType($expected, gettype($actual));
    }

    /**
     * @param string $expected
     * @param string $actual
     * @return bool
     */
    protected function typeMatchesType($expected, $actual)
    {
        if ($expected === '*') {
            return true;
        }
        foreach ($this->getExpectedTypesAsArray($expected) as $expectedType) {
            if ($expectedType === $actual) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $expected
     * @return array
     */
    protected function getExpectedTypesAsArray($expected)
    {
        if (($delimiterPosition = strpos($expected, ':')) !== false) {
            $expected = substr($expected, $delimiterPosition);
        }
        if (strpos($expected, '|') !== false) {
            return explode('|', $expected);
        }
        return array($expected);
    }
}
