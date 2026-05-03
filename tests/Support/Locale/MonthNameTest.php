<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Support\Locale;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Support\Locale\MonthName;

final class MonthNameTest extends TestCase
{
    public function testMonthNameCountIsTwelve(): void
    {
        $this->assertCount(12, MonthName::EN);
        $this->assertCount(12, MonthName::EN_UPPER);
        $this->assertCount(12, MonthName::EN_SHORT);
        $this->assertCount(12, MonthName::EN_SHORT_UPPER);
    }

    public function testMonthNameFirstAndLastValuesMatch(): void
    {
        $this->assertSame('January', MonthName::EN[0]);
        $this->assertSame('December', MonthName::EN[11]);
        $this->assertSame('Jan', MonthName::EN_SHORT[0]);
        $this->assertSame('Dec', MonthName::EN_SHORT[11]);
    }
}
