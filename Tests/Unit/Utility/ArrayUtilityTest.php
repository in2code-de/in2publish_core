<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Utility;

use In2code\In2publishCore\Tests\UnitTestCase;
use In2code\In2publishCore\Utility\ArrayUtility;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversMethod(ArrayUtility::class, 'removeFromArrayByKey')]
#[CoversMethod(ArrayUtility::class, 'normalizeArray')]
class ArrayUtilityTest extends UnitTestCase
{
    public static function removeFromArrayByKeyReturnsVoidDataProvider(): array
    {
        return [
            'superfluous_key' => [['abc' => '1', 'def' => 1236, 'ghi' => 'acb'], ['def', 'xy'], 2],
            'all_keys' => [['a' => 'a', 'b' => 'x', 'c' => ''], ['a', 'b', 'c'], 0],
            'last_key' => [['a' => 123, 'b' => 234, 'c' => 456], ['c'], 2],
            'single_not_existing_key' => [['a' => '', 'b' => '', 'c' => ''], ['x'], 3],
        ];
    }

    /**
     * @param array $array
     * @param array $keysToRemove
     * @param int $countArray count() of manipulated array
     *
     * @return void
     */
    #[DataProvider('removeFromArrayByKeyReturnsVoidDataProvider')]
    public function testRemoveFromArrayByKeyRemovesAllEntriesWithTheGivenKeys(
        array $array,
        array $keysToRemove,
        int $countArray
    ) {
        $array = ArrayUtility::removeFromArrayByKey($array, $keysToRemove);
        foreach ($keysToRemove as $key) {
            $this->assertFalse(isset($array[$key]));
        }
        $this->assertSame(count($array), $countArray);
    }

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
