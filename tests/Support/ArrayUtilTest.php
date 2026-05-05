<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Support\ArrayUtil;

final class ArrayUtilTest extends TestCase
{
    public function testGetReturnsExistingValueEvenWhenNull(): void
    {
        $array = ['key' => null];

        $this->assertNull(ArrayUtil::get($array, 'key', 'fallback'));
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $this->assertSame('default', ArrayUtil::get([], 'missing', 'default'));
    }

    public function testFlattenRecursivelyFlattensNestedArrays(): void
    {
        $array = ['a', ['b', ['c', 'd']], 'e'];

        $this->assertSame(['a', 'b', 'c', 'd', 'e'], ArrayUtil::flatten($array));
    }

    public function testIsEmptyValuesReturnsTrueWhenAllItemsEmpty(): void
    {
        $this->assertTrue(ArrayUtil::isEmptyValues(['', 0, []]));
    }

    public function testIsEmptyValuesRecursiveChecksNestedArrays(): void
    {
        $this->assertTrue(ArrayUtil::isEmptyValues(['', [''], []], true));
        $this->assertFalse(ArrayUtil::isEmptyValues(['', ['value'], []], true));
    }

    public function testMakeAssociativeLimitsToKeysAndUsesNullForMissingValues(): void
    {
        $keys = ['a', 'b', 'c'];
        $values = ['a' => 1, 'c' => 3];

        $this->assertSame(['a' => 1, 'b' => null, 'c' => 3], ArrayUtil::makeAssociative($keys, $values));
    }

    public function testIsListReturnsTrueForSequentialArray(): void
    {
        $this->assertTrue(ArrayUtil::isList([1, 2, 3]));
    }

    public function testIsListReturnsTrueForEmptyArray(): void
    {
        $this->assertTrue(ArrayUtil::isList([]));
    }

    public function testIsListReturnsFalseForAssociativeArray(): void
    {
        $this->assertFalse(ArrayUtil::isList(['a' => 1, 'b' => 2]));
    }

    public function testIsListReturnsFalseForGappedArray(): void
    {
        $this->assertFalse(ArrayUtil::isList([0 => 'a', 2 => 'b']));
    }

    public function testIsAssociativeReturnsTrueForAssociativeArray(): void
    {
        $this->assertTrue(ArrayUtil::isAssociative(['a' => 1, 'b' => 2]));
    }

    public function testIsAssociativeReturnsFalseForSequentialArray(): void
    {
        $this->assertFalse(ArrayUtil::isAssociative([1, 2, 3]));
    }

    public function testIsAssociativeReturnsFalseForEmptyArray(): void
    {
        $this->assertFalse(ArrayUtil::isAssociative([]));
    }

    public function testIsListThrowsTypeErrorForNonArray(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line */
        ArrayUtil::isList('not an array');
    }
}
