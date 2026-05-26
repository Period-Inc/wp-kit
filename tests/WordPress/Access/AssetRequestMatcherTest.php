<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress\Access;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\Access\AssetRequestMatcher;

final class AssetRequestMatcherTest extends TestCase
{
    public function testMatchesDefaultUploadsPrefix(): void
    {
        $matcher = new AssetRequestMatcher();

        $this->assertTrue($matcher->matches('/wp-content/uploads/2026/01/file.pdf'));
    }

    public function testDoesNotMatchUnrelatedPath(): void
    {
        $matcher = new AssetRequestMatcher();

        $this->assertFalse($matcher->matches('/wp-content/themes/mytheme/style.css'));
    }

    public function testDoesNotMatchRootPath(): void
    {
        $matcher = new AssetRequestMatcher();

        $this->assertFalse($matcher->matches('/'));
    }

    public function testStripsQueryStringBeforeMatching(): void
    {
        $matcher = new AssetRequestMatcher();

        $this->assertTrue($matcher->matches('/wp-content/uploads/file.pdf?v=123&foo=bar'));
    }

    public function testQueryStringAloneDoesNotMatch(): void
    {
        $matcher = new AssetRequestMatcher();

        $this->assertFalse($matcher->matches('/downloads/file.pdf?path=/wp-content/uploads/x'));
    }

    public function testCustomPrefix(): void
    {
        $matcher = new AssetRequestMatcher(['/protected/assets/']);

        $this->assertTrue($matcher->matches('/protected/assets/secret.pdf'));
        $this->assertFalse($matcher->matches('/wp-content/uploads/file.pdf'));
    }

    public function testMultiplePrefixes(): void
    {
        $matcher = new AssetRequestMatcher(['/wp-content/uploads/', '/private/files/']);

        $this->assertTrue($matcher->matches('/wp-content/uploads/doc.pdf'));
        $this->assertTrue($matcher->matches('/private/files/report.xlsx'));
        $this->assertFalse($matcher->matches('/public/image.png'));
    }

    public function testEmptyPrefixListNeverMatches(): void
    {
        $matcher = new AssetRequestMatcher([]);

        $this->assertFalse($matcher->matches('/wp-content/uploads/file.pdf'));
    }

    public function testDeterministic(): void
    {
        $matcher = new AssetRequestMatcher();
        $uri     = '/wp-content/uploads/file.pdf';

        $this->assertSame($matcher->matches($uri), $matcher->matches($uri));
    }
}
