<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Testing\Tests\Configuration;

/*
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
 */

use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Domain\Service\Processor\ProcessorInterface;
use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test some configuration values if they are in between defined ranges or make sense in the current context and much
 * more.
 */
class ConfigurationValuesTest implements TestCaseInterface
{
    public const PROCESSOR_INTERFACE = ProcessorInterface::class;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * ConfigurationValuesTest constructor.
     */
    public function __construct()
    {
        $this->configuration = GeneralUtility::makeInstance(ConfigContainer::class)->get();
    }

    /**
     * @return TestResult
     */
    public function run(): TestResult
    {
        $errors = [];

        // test the settings
        if (
            true === (bool)$this->configuration['debug']['disableParentRecords']
            && true === (bool)$this->configuration['view']['records']['breadcrumb']
        ) {
            $errors[] = 'configuration.breadcrumb_and_disable_parents_active';
        }

        // construct TestResult
        if (!empty($errors)) {
            return new TestResult('configuration.options_invalid', TestResult::ERROR, $errors);
        }
        return new TestResult('configuration.options_valid');
    }

    /**
     * @return array
     */
    public function getDependencies(): array
    {
        return [
            ConfigurationFormatTest::class,
        ];
    }
}
