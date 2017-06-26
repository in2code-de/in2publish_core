<?php
namespace In2code\In2publishCore\Service\Configuration;

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

use In2code\In2publishCore\Utility\Configuration\ExtensionConfigurationAccessor;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Central class for configuration set in the Extension Manager, solely for in2publish_core.
 * This should be the only class using ExtensionConfigurationAccessor::getExtensionConfiguration.
 */
class In2publishConfigurationService implements SingletonInterface
{
    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * In2publishConfigurationService constructor.
     */
    public function __construct()
    {
        $this->configuration = ExtensionConfigurationAccessor::getExtensionConfiguration();
    }

    /**
     * @return null|string
     */
    public function getPathToConfiguration()
    {
        if (isset($this->configuration['pathToConfiguration'])) {
            return (string)$this->configuration['pathToConfiguration'];
        }
        return null;
    }
}
