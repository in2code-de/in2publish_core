<?php
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

use In2code\In2publishCore\Utility\ArrayUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Utility\ArrayUtility
 */
class ArrayUtilityTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function removeFromArrayByKeyReturnsVoidDataProvider()
    {
        return [
            'superfluous_key' => [['abc' => '1', 'def' => 1236, 'ghi' => 'acb'], ['def', 'xy'], 2],
            'all_keys' => [['a' => 'a', 'b' => 'x', 'c' => ''], ['a', 'b', 'c'], 0],
            'last_key' => [['a' => 123, 'b' => 234, 'c' => 456], ['c'], 2],
            'single_not_existing_key' => [['a' => '', 'b' => '', 'c' => ''], ['x'], 3],
        ];
    }

    /**
     * @dataProvider removeFromArrayByKeyReturnsVoidDataProvider
     * @covers ::removeFromArrayByKey
     *
     * @param array $array
     * @param array $keysToRemove
     * @param int $countArray count() of manipulated array
     *
     * @return void
     */
    public function testRemoveFromArrayByKeyRemovesAllEntriesWithTheGivenKeys(
        array $array,
        array $keysToRemove,
        $countArray
    ) {
        $array = ArrayUtility::removeFromArrayByKey($array, $keysToRemove);
        foreach ($keysToRemove as $key) {
            $this->assertFalse(isset($array[$key]));
        }
        $this->assertSame(count($array), $countArray);
    }

    /**
     * @covers ::normalizeArray
     */
    public function testNormalizeArrayConvertsBoolAndIntAndRemovesEmptyValues()
    {
        $arrayToNormalize = [
            '12',
            'falSe',
            51234,
            'TRue',
            '17.9',
            '0',
            [],
            ['hi' => null],
            ['hi' => 'NULL'],
            'blah' => [],
            'stuff' => ['12', 'TRue', 'FALSE', '12,4', '0',],
            null,
            'NULL',
        ];
        $expectedArray = [12, false, 51234, true, '17.9', 0, 'stuff' => [12, true, false, '12,4', 0]];
        $this->assertSame($expectedArray, ArrayUtility::normalizeArray($arrayToNormalize));
    }
}
