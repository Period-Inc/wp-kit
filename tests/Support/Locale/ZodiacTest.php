<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\Support\Locale;

use PHPUnit\Framework\TestCase;
use Period\WpKit\Support\Locale\Zodiac;

final class ZodiacTest extends TestCase
{
    public function testZodiacCountIsTwelve(): void
    {
        $this->assertCount(12, Zodiac::EN);
        $this->assertCount(12, Zodiac::EN_UPPER);
        $this->assertCount(12, Zodiac::JA);
        $this->assertCount(12, Zodiac::JA_KANA);
        $this->assertCount(12, Zodiac::CN);
    }

    public function testZodiacFirstAndLastValuesMatch(): void
    {
        $this->assertSame('Aries', Zodiac::EN[0]);
        $this->assertSame('Pisces', Zodiac::EN[11]);
        $this->assertSame('牡羊座', Zodiac::JA[0]);
        $this->assertSame('魚座', Zodiac::JA[11]);
    }
}
