<?php
declare(strict_types=1);

namespace Period\WpKit\Tests\WordPress;

use Period\WpKit\WordPress\PostAssets;
use Period\WpKit\WordPress\PostMetaManager;
use PHPUnit\Framework\TestCase;

final class PostAssetsTest extends TestCase
{
    /** @runInSeparateProcess */
    public function testReturnsPostAssetValues(): void
    {
        eval('function metadata_exists(string $type, int $id, string $key): bool { return true; }');
        eval('function get_post_meta(int $id, string $key, bool $single = false): mixed {
            return match ($key) {
                "csscode" => "body { color: red; }",
                "cssfile" => "/assets/post.css",
                "jscode" => "console.log(1);",
                "jsfile" => "/assets/post.js",
                default => "",
            };
        }');

        $assets = new PostAssets(new PostMetaManager());

        $this->assertSame('body { color: red; }', $assets->cssCode(1));
        $this->assertSame('/assets/post.css', $assets->cssFile(1));
        $this->assertSame('console.log(1);', $assets->jsCode(1));
        $this->assertSame('/assets/post.js', $assets->jsFile(1));
    }

    /** @runInSeparateProcess */
    public function testReturnsEmptyStringWhenMetaDoesNotExist(): void
    {
        eval('function metadata_exists(string $type, int $id, string $key): bool { return false; }');

        $assets = new PostAssets(new PostMetaManager());

        $this->assertSame('', $assets->cssCode(1));
        $this->assertSame('', $assets->cssFile(1));
        $this->assertSame('', $assets->jsCode(1));
        $this->assertSame('', $assets->jsFile(1));
    }
}
