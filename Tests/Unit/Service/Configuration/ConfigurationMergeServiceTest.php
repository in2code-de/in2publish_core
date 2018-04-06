<?php
namespace In2code\In2publishCore\Tests\Unit\Service\Configuration;

use In2code\In2publishCore\Service\Configuration\ConfigurationMergeService;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Configuration\ConfigurationMergeService
 */
class ConfigurationMergeServiceTest extends UnitTestCase
{
    /**
     * @var ConfigurationMergeService
     */
    public $subject;

    public function setUp()
    {
        $this->subject = new ConfigurationMergeService();
    }

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
        $result = $this->subject->mergeConfiguration($original, $additional);

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
            'foo' => $value1,
            'bar' => $value3,
            'baz' => $value4,
        ];

        // Act
        $result = $this->subject->mergeConfiguration($original, $additional);

        // Assert
        $this->assertEquals($expectedResult, $result);
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
        $result = $this->subject->mergeConfiguration($original, $additional);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @test
     * @covers ::mergeConfiguration()
     */
    public function canMergenNestedArraysRecursively()
    {
        $value1 = 'lorem';
        $value2original = [
            0 => 'red',
            'sub1' => 'green',
            'sub2' => 'blue',
        ];
        $value2additional = [
            0 => 'black',
            'sub1' => 'white',
            'sub2' => 'blue',
            'sub3' => 'grey',
        ];
        $value2Expected = [
            0 => 'red',
            1 => 'black',
            'sub1' => 'white',
            'sub2' => 'blue',
            'sub3' => 'grey',
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
        $result = $this->subject->mergeConfiguration($original, $additional);

        // Assert
        $this->assertEquals($expectedResult, $result);
    }
}
