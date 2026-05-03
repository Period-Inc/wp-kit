<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Support\ArgsResolver;

final class ArgsResolverTest extends TestCase
{
    private ArgsResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ArgsResolver();
    }

    public function testShallowResolvesWithArrayMergeBehavior(): void
    {
        $result = $this->resolver->resolve([
            'a' => 1,
            'b' => ['x' => 10, 'y' => 20],
        ], [
            'a' => 2,
            'b' => ['x' => 30],
        ], 'shallow');

        $this->assertSame(["a" => 2, "b" => ["x" => 30]], $result);
    }

    public function testDeepResolvesAssociativeArraysRecursively(): void
    {
        $result = $this->resolver->resolve([
            'a' => 1,
            'b' => ['x' => 10, 'y' => 20],
        ], [
            'b' => ['x' => 30],
        ], 'deep');

        $this->assertSame([
            'a' => 1,
            'b' => ['x' => 30, 'y' => 20],
        ], $result);
    }

    public function testDeepReplacesSequentialArrays(): void
    {
        $result = $this->resolver->resolve([
            'a' => [1, 2, 3],
        ], [
            'a' => [4, 5],
        ], 'deep');

        $this->assertSame(['a' => [4, 5]], $result);
    }

    public function testUnknownModeUsesDeepMerge(): void
    {
        $result = $this->resolver->resolve([
            'a' => ['x' => 1, 'y' => 2],
        ], [
            'a' => ['y' => 3],
        ], 'unknown');

        $this->assertSame(['a' => ['x' => 1, 'y' => 3]], $result);
    }
}
