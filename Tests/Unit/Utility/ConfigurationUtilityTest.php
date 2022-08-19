<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Utility;

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

use In2code\In2publishCore\Tests\UnitTestCase;
use In2code\In2publishCore\Utility\ConfigurationUtility;

/**
 * @coversDefaultClass \In2code\In2publishCore\Utility\ConfigurationUtility
 */
class ConfigurationUtilityTest extends UnitTestCase
{
    /**
     * @test
     * @covers ::mergeConfiguration()
     */
    public function addsNewKeyValues()
    {
        $value1 = 'lorem';
        $value2 = 'ipsum';
        $value3 = 'dolor';
        $value4 = 'sit';

        // Arrange
        $original = [
            1 => $value1,
            'foo' => $value2,
        ];
        $additional = [
            2 => $value3,
            'bar' => $value4,
        ];

        // Act
        $result = ConfigurationUtility::mergeConfiguration($original, $additional);

        // Assert
        $this->assertCount(4, $result);
        $this->assertContains($value1, $result);
        $this->assertContains($value2, $result);
        $this->assertContains($value3, $result);
        $this->assertContains($value4, $result);
        $this->assertArrayHasKey('foo', $result);
        $this->assertArrayHasKey('bar', $result);
    }

    /**
     * @test
     * @covers ::mergeConfiguration()
     */
    public function overwritesValuesOfAlphanumericKeys()
    {
        $value1 = 'lorem';
        $value2 = 'ipsum';
        $value3 = 'dolor';
        $value4 = 'sit';

        // Arrange
        $original = [
            'foo' => $value1,
            'bar' => $value2,
        ];
        $additional = [
            'bar' => $value3,
            'baz' => $value4,
        ];
        $expectedResult = [
            'bar' => $value3,
            'baz' => $value4,
            'foo' => $value1,
        ];

        // Act
        $result = ConfigurationUtility::mergeConfiguration($original, $additional);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @test
     * @covers ::mergeConfiguration()
     */
    public function doesNotOverwriteButAddsValuesOfNumericKeys()
    {
        $value1 = 'lorem';
        $value2 = 'ipsum';
        $value3 = 'dolor';
        $value4 = 'sit';
        $value5 = 'amet';

        // Arrange
        $original = [
            0 => $value1,
            'foo' => $value5,
            1 => $value2,
        ];
        $additional = [
            1 => $value3,
            2 => $value4,
        ];
        $expectedResult = [
            'foo' => $value5,
            0 => $value1,
            1 => $value2,
            2 => $value3,
            3 => $value4,
        ];

        // Act
        $result = ConfigurationUtility::mergeConfiguration($original, $additional);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @test
     * @covers ::mergeConfiguration()
     */
    public function doesOverwriteNumericKeysOfDefinitionArrays()
    {
        $original = [
            'definition' => [
                5 => 'strawberries',
                8 => 'apples',
                19 => 'bananas',
                1 => 'nuts',
            ],
        ];

        $additional = [
            'definition' => [
                2 => 'mango',
                8 => 'pear',
                4 => 'peach',
                1 => 'passionfruit',
            ],
        ];

        $expectedResult = [
            'definition' => [
                2 => 'mango',
                8 => 'pear',
                4 => 'peach',
                1 => 'passionfruit',
                5 => 'strawberries',
                19 => 'bananas',
            ],
        ];

        // Act
        $result = ConfigurationUtility::mergeConfiguration($original, $additional);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @test
     * @covers ::mergeConfiguration()
     */
    public function canMergeNestedArraysRecursively()
    {
        $value1 = 'lorem';
        $value2original = [
            0 => 'red',
            1 => 'rose',
            999 => 'burgund',
            'sub1' => 'green',
            'sub2' => 'blue',
            'sub999' => 'magenta',
        ];
        $value2additional = [
            0 => 'black',
            999 => 'lila',
            'sub1' => 'white',
            'sub2' => 'blue',
            'sub3' => 'grey',
            'sub4' => null,
        ];
        $value2Expected = [
            0 => 'red',
            1 => 'rose',
            999 => 'burgund',
            1000 => 'black',
            1001 => 'lila',
            'sub1' => 'white',
            'sub2' => 'blue',
            'sub3' => 'grey',
            'sub4' => null,
            'sub999' => 'magenta',
        ];
        $value3 = 'dolor';

        // Arrange
        $original = [
            'foo' => $value1,
            'bar' => $value2original,
            'baz' => $value3,
        ];
        $additional = [
            'bar' => $value2additional,
        ];
        $expectedResult = [
            'foo' => $value1,
            'bar' => $value2Expected,
            'baz' => $value3,
        ];

        // Act
        $result = ConfigurationUtility::mergeConfiguration($original, $additional);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @test
     * @covers ::mergeConfiguration()
     */
    public function sortsValuesByDefinedOrder()
    {
        $original = [
            'foo' => 'bar',
            'baz' => 'bem',
            'boo' => 13,
            52 => 'fem',
            13 => 'bam',
            754 => 274,
        ];
        $expectedResult = $additional = [
            754 => 274,
            'baz' => 'bem',
            52 => 'fem',
            'boo' => 13,
            13 => 'bam',
            'foo' => 'bar',
        ];

        // Act
        $result = ConfigurationUtility::mergeConfiguration($original, $additional);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    /**
     * @test
     * @covers ::mergeConfiguration()
     */
    public function valuesAreRemovedIfTheValueIsUnset()
    {
        $original = [
            'foo' => 'bar',
            'baz' => 'bem',
        ];
        $additional = [
            'foo' => '__UNSET',
        ];

        $expectedResult = [
            'baz' => 'bem',
        ];

        // Act
        $result = ConfigurationUtility::mergeConfiguration($original, $additional);

        // Assert
        $this->assertSame($expectedResult, $result);
    }
}
