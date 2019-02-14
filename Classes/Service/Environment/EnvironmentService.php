<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Service\Environment;

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

use In2code\In2publishCore\Config\ConfigContainer;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class EnvironmentService
 */
class EnvironmentService implements SingletonInterface
{
    const STATE_TESTS_FAILING = 'tests_failing';
    const STATE_TESTS_NEVER_RAN = 'tests_never_ran';
    const STATE_PACKAGES_CHANGED = 'environment_changed';
    const STATE_CONFIGURATION_CHANGED = 'configuration_changed';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * EnvironmentService constructor.
     */
    public function __construct()
    {
        $this->registry = $this->getRegistry();
    }

    /**
     * @param bool $success
     */
    public function setTestResult(bool $success)
    {
        $this->registry->set(
            'tx_in2publishcore',
            'test_result',
            [
                'success' => $success,
                'packages_hash' => $this->getPackagesHash(),
                'configuration_hash' => $this->getConfigurationHash(),
            ]
        );
    }

    /**
     * @return array
     */
    public function getTestStatus(): array
    {
        $statusArray = [];
        $testResults = $this->registry->get('tx_in2publishcore', 'test_result', false);

        if ($testResults === false) {
            return [static::STATE_TESTS_NEVER_RAN];
        }
        if ($testResults['packages_hash'] !== $this->getPackagesHash()) {
            $statusArray[] = static::STATE_PACKAGES_CHANGED;
        }
        if ($testResults['configuration_hash'] !== $this->getConfigurationHash()) {
            $statusArray[] = static::STATE_CONFIGURATION_CHANGED;
        }
        if ($testResults['success'] !== true) {
            $statusArray[] = static::STATE_TESTS_FAILING;
        }
        return $statusArray;
    }

    /**
     * @return string
     */
    protected function getPackagesHash(): string
    {
        return sha1(json_encode($this->getActivePackagesArray()));
    }

    /**
     * @return PackageInterface[]
     *
     * @codeCoverageIgnore
     */
    protected function getActivePackagesArray(): array
    {
        return GeneralUtility::makeInstance(PackageManager::class)->getActivePackages();
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getConfigurationHash(): string
    {
        return sha1(serialize(GeneralUtility::makeInstance(ConfigContainer::class)->get()));
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getRegistry(): Registry
    {
        return GeneralUtility::makeInstance(Registry::class);
    }
}
