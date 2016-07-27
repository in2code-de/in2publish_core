<?php
namespace In2code\In2publishCore\Tests\Unit\Utility\Configuration;

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

use In2code\In2publishCore\Tests\Helper\TestingHelper;
use In2code\In2publishCore\Utility\Configuration\ExtensionConfigurationAccessor;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Utility\Configuration\ExtensionConfigurationAccessor
 */
class ExtensionConfigurationAccessorTest extends UnitTestCase
{
    /**
     * @covers ::getExtensionConfiguration
     */
    public function testGetExtensionConfigurationReturnsEmptyArrayIfConfigurationIsNotAvailable()
    {
        $this->assertSame(array(), ExtensionConfigurationAccessor::getExtensionConfiguration());
    }

    /**
     * @covers ::getExtensionConfiguration
     */
    public function testGetExtensionConfigurationReturnsIn2publishCoreConfigurationAndSupportsSerializedArrays()
    {
        TestingHelper::setExtConf(array('foo' => 'bar'));
        $this->assertSame(array('foo' => 'bar'), ExtensionConfigurationAccessor::getExtensionConfiguration());
    }

    /**
     * @covers ::getExtensionConfiguration
     */
    public function testGetExtensionConfigurationReturnsConfigurationForOtherExtensionKeys()
    {
        TestingHelper::setExtConf(array('foo' => 'bar'), 'baz');
        $this->assertSame(array('foo' => 'bar'), ExtensionConfigurationAccessor::getExtensionConfiguration('baz'));
    }

    /**
     * @covers ::getExtensionConfiguration
     */
    public function testGetExtensionConfigurationSupportsArrays()
    {
        TestingHelper::setExtConf(array('baz' => 'boo'), 'baz', false);
        $this->assertSame(array('baz' => 'boo'), ExtensionConfigurationAccessor::getExtensionConfiguration('baz'));
    }

    /**
     * @covers ::getExtensionConfiguration
     */
    public function testGetExtensionConfigurationReturnsEmptyArrayForUnsupportedValues()
    {
        TestingHelper::setExtConf(new \stdClass());
        $this->assertSame(array(), ExtensionConfigurationAccessor::getExtensionConfiguration());
    }
}
