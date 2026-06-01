<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpKit\Support\ImageUtil;

final class ImageUtilTest extends TestCase
{
    public function testRatioReturnsWidthDividedByHeight(): void
    {
        $this->assertSame(16 / 9, ImageUtil::ratio(16, 9));
    }

    public function testRatioReturnsZeroWhenHeightIsZeroOrNegative(): void
    {
        $this->assertSame(0.0, ImageUtil::ratio(16, 0));
        $this->assertSame(0.0, ImageUtil::ratio(16, -4));
    }

    public function testAspectRatioReducesToSixteenNine(): void
    {
        $this->assertSame('16:9', ImageUtil::aspectRatio(1920, 1080));
    }

    public function testAspectRatioReturnsOneToOneForSquareValues(): void
    {
        $this->assertSame('1:1', ImageUtil::aspectRatio(1200, 1200));
    }

    public function testAspectRatioReturnsEmptyWhenWidthOrHeightIsNonPositive(): void
    {
        $this->assertSame('', ImageUtil::aspectRatio(0, 1080));
        $this->assertSame('', ImageUtil::aspectRatio(1920, 0));
    }

    public function testOrientationReturnsLandscapePortraitSquareAndUnknown(): void
    {
        $this->assertSame('landscape', ImageUtil::orientation(1920, 1080));
        $this->assertSame('portrait', ImageUtil::orientation(1080, 1920));
        $this->assertSame('square', ImageUtil::orientation(1000, 1000));
        $this->assertSame('unknown', ImageUtil::orientation(0, 100));
        $this->assertSame('unknown', ImageUtil::orientation(100, -1));
    }

    public function testPaddingTopPercentCalculatesRatioAsPercentage(): void
    {
        $this->assertSame(56.25, ImageUtil::paddingTopPercent(16, 9));
    }

    public function testNormalizeSizeClampsNegativeValuesToZero(): void
    {
        $this->assertSame(['width' => 0, 'height' => 0], ImageUtil::normalizeSize(-100, -200));
        $this->assertSame(['width' => 120, 'height' => 0], ImageUtil::normalizeSize(120, -20));
    }
}
