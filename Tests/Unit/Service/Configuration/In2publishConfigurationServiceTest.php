<?php
namespace In2code\In2publishCore\Tests\Unit\Service\Configuration;

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

use In2code\In2publishCore\Service\Configuration\In2publishConfigurationService;
use In2code\In2publishCore\Tests\Helper\TestingHelper;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Configuration\In2publishConfigurationService
 */
class In2publishConfigurationServiceTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::getPathToConfiguration
     */
    public function testGetPathToConfigurationReturnsNullIfOptionWasNotSet()
    {
        TestingHelper::setExtConf(array());

        $configurationService = new In2publishConfigurationService();
        $this->assertNull($configurationService->getPathToConfiguration());
    }

    /**
     * @covers ::__construct
     * @covers ::getPathToConfiguration
     */
    public function testGetPathToConfigurationReturnsConfiguredPath()
    {
        TestingHelper::setExtConf(array('pathToConfiguration' => 'foo/bar/baz'));

        $configurationService = new In2publishConfigurationService();
        $this->assertSame('foo/bar/baz', $configurationService->getPathToConfiguration());
    }
}
