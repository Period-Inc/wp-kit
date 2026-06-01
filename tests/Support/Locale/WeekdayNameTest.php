<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\Support\Locale;

use PHPUnit\Framework\TestCase;
use Period\WpKit\Support\Locale\WeekdayName;

final class WeekdayNameTest extends TestCase
{
    public function testWeekdayNameCountIsSeven(): void
    {
        $this->assertCount(7, WeekdayName::EN);
        $this->assertCount(7, WeekdayName::EN_SHORT);
        $this->assertCount(7, WeekdayName::EN_UPPER);
        $this->assertCount(7, WeekdayName::EN_SHORT_UPPER);
        $this->assertCount(7, WeekdayName::JA);
        $this->assertCount(7, WeekdayName::JA_SHORT);
    }

    public function testWeekdayNameStartsOnSunday(): void
    {
        $this->assertSame('Sunday', WeekdayName::EN[0]);
        $this->assertSame('日曜日', WeekdayName::JA[0]);
        $this->assertSame('Sat', WeekdayName::EN_SHORT[6]);
        $this->assertSame('土', WeekdayName::JA_SHORT[6]);
    }
}
