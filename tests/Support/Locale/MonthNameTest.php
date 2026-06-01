<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\Support\Locale;

use PHPUnit\Framework\TestCase;
use Period\WpKit\Support\Locale\MonthName;

final class MonthNameTest extends TestCase
{
    public function testMonthNameCountIsTwelve(): void
    {
        $this->assertCount(12, MonthName::EN);
        $this->assertCount(12, MonthName::EN_UPPER);
        $this->assertCount(12, MonthName::EN_SHORT);
        $this->assertCount(12, MonthName::EN_SHORT_UPPER);
        $this->assertCount(12, MonthName::JA);
        $this->assertCount(12, MonthName::JA_FULL);
        $this->assertCount(12, MonthName::JA_TRADITIONAL);
    }

    public function testMonthNameFirstAndLastValuesMatch(): void
    {
        $this->assertSame('January', MonthName::EN[0]);
        $this->assertSame('December', MonthName::EN[11]);
        $this->assertSame('Jan', MonthName::EN_SHORT[0]);
        $this->assertSame('Dec', MonthName::EN_SHORT[11]);
        $this->assertSame('1月', MonthName::JA[0]);
        $this->assertSame('12月', MonthName::JA[11]);

        $this->assertSame('一月', MonthName::JA_FULL[0]);
        $this->assertSame('十二月', MonthName::JA_FULL[11]);

        $this->assertSame('睦月', MonthName::JA_TRADITIONAL[0]);
        $this->assertSame('師走', MonthName::JA_TRADITIONAL[11]);
    }
}
