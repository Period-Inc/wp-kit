<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\PostMetaManager;

final class PostMetaManagerTest extends TestCase
{
    public function testGetReturnsNullWithoutWordPress(): void
    {
        $this->assertNull((new PostMetaManager())->get(1, 'key'));
    }

    public function testSetIsNoopWithoutWordPress(): void
    {
        // Should not throw
        (new PostMetaManager())->set(1, 'key', 'value');
        $this->assertTrue(true);
    }

    public function testHasReturnsFalseWithoutWordPress(): void
    {
        $this->assertFalse((new PostMetaManager())->has(1, 'key'));
    }

    /** @runInSeparateProcess */
    public function testGetReturnsValue(): void
    {
        eval('function get_post_meta(int $id, string $key, bool $single = false): mixed { return "hello"; }');

        $this->assertSame('hello', (new PostMetaManager())->get(1, 'title'));
    }

    /** @runInSeparateProcess */
    public function testGetReturnsEmptyString(): void
    {
        eval('function get_post_meta(int $id, string $key, bool $single = false): mixed { return ""; }');

        $this->assertSame('', (new PostMetaManager())->get(1, 'title'));
    }

    public function testSetDoesNotThrowWhenWordPressIsAvailable(): void
    {
        // bootstrap.php stubs update_post_meta — verifying no exception is thrown
        (new PostMetaManager())->set(1, 'title', 'Hello');
        $this->assertTrue(true);
    }

    /** @runInSeparateProcess */
    public function testHasReturnsTrueWhenMetaExists(): void
    {
        eval('function metadata_exists(string $type, int $id, string $key): bool { return true; }');

        $this->assertTrue((new PostMetaManager())->has(1, 'title'));
    }

    /** @runInSeparateProcess */
    public function testHasReturnsFalseWhenMetaAbsent(): void
    {
        eval('function metadata_exists(string $type, int $id, string $key): bool { return false; }');

        $this->assertFalse((new PostMetaManager())->has(1, 'missing'));
    }
}
