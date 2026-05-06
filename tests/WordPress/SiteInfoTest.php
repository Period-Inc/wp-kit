<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\WordPress;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\WordPress\SiteInfo;

final class SiteInfoTest extends TestCase
{
    public function testNameReturnsStringWhenWordPressAbsent(): void
    {
        $info = new SiteInfo();

        $this->assertIsString($info->name());
    }

    public function testDescriptionReturnsStringWhenWordPressAbsent(): void
    {
        $info = new SiteInfo();

        $this->assertIsString($info->description());
    }

    public function testCharsetFallbackIsUtf8(): void
    {
        $info = new SiteInfo();

        $this->assertSame('UTF-8', $info->charset());
    }

    public function testLanguageFallbackIsEn(): void
    {
        $info = new SiteInfo();

        $this->assertSame('en', $info->language());
    }

    public function testUrlReturnsStringWhenWordPressAbsent(): void
    {
        $info = new SiteInfo();

        $this->assertIsString($info->url());
    }

    public function testThemeUriReturnsStringWhenWordPressAbsent(): void
    {
        $info = new SiteInfo();

        $this->assertIsString($info->themeUri());
    }

    public function testNameReturnsEmptyStringWhenWordPressAbsent(): void
    {
        $info = new SiteInfo();

        $this->assertSame('', $info->name());
    }

    public function testUrlReturnsEmptyStringWhenWordPressAbsent(): void
    {
        $info = new SiteInfo();

        $this->assertSame('', $info->url());
    }

    public function testThemeUriReturnsEmptyStringWhenWordPressAbsent(): void
    {
        $info = new SiteInfo();

        $this->assertSame('', $info->themeUri());
    }
}
