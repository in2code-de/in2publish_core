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

use In2code\In2publishCore\Testing\Tests\TestCaseInterface;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Utility\ConfigurationUtility;

/**
 * Test some configuration values if they are in between defined ranges or make sense in the current context and much
 * more.
 */
class ConfigurationValuesTest implements TestCaseInterface
{
    /**
     * @var array
     */
    protected $configuration = array();

    /**
     * ConfigurationValuesTest constructor.
     */
    public function __construct()
    {
        $this->configuration = ConfigurationUtility::getConfiguration();
    }

    /**
     * @return TestResult
     */
    public function run()
    {
        $errors = array();

        // test the settings
        if ($this->configuration['log']['logLevel'] > 7) {
            $errors[] = 'configuration.loglevel_too_high';
        }
        if ($this->configuration['log']['logLevel'] < 0) {
            $errors[] = 'configuration.loglevel_too_low';
        }
        if (!is_file($this->configuration['sshConnection']['privateKeyFileAndPathName'])) {
            $errors[] = 'configuration.private_key_invalid';
        }
        if (!is_file($this->configuration['sshConnection']['publicKeyFileAndPathName'])) {
            $errors[] = 'configuration.public_key_invalid';
        }
        if (true === (bool)$this->configuration['debug']['disableParentRecords']
            && true === (bool)$this->configuration['view']['records']['breadcrumb']
        ) {
            $errors[] = 'configuration.breadcrumb_and_disable_parents_active';
        }
        if (!is_dir($this->configuration['backup']['publishTableCommand']['backupLocation'])) {
            $errors[] = 'configuration.backup_folder_invalid';
        }
        if (true === (bool)$this->configuration['backup']['publishTableCommand']['zipBackup']
            && !class_exists('\ZipArchive')
        ) {
            $errors[] = 'configuration.zip_extension_not_installed';
        }
        $missingProcessors = array();
        foreach ($this->configuration['tca']['processor'] as $processorClass) {
            if (!class_exists($processorClass)) {
                $missingProcessors[] = $processorClass;
            }
        }
        if (!empty($missingProcessors)) {
            $errors = array_merge($errors, array('configuration.tca_processor_missing'), $missingProcessors);
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
    public function getDependencies()
    {
        return array(
            'In2code\\In2publishCore\\Testing\\Tests\\Configuration\\ConfigurationFormatTest',
        );
    }
}
