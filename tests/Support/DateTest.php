<?php

declare(strict_types=1);

namespace Period\WpFramework\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpFramework\Support\Date;

final class DateTest extends TestCase
{
    public function testFromStringReturnsCorrectComponents(): void
    {
        $date = Date::fromString('2024-02-15');

        $this->assertSame(2024, $date->year());
        $this->assertSame(2, $date->month());
        $this->assertSame(15, $date->day());
    }

    public function testDaysInMonthAndLeapYear(): void
    {
        $date = Date::fromString('2024-02-01');

        $this->assertSame(29, $date->daysInMonth());
        $this->assertTrue($date->isLeapYear());

        $date = Date::fromString('2023-02-01');
        $this->assertSame(28, $date->daysInMonth());
        $this->assertFalse($date->isLeapYear());
    }

    public function testWeekdayReturnsSundayZero(): void
    {
        $date = Date::fromString('2024-02-18');

        $this->assertSame(0, $date->weekday());
    }

    public function testNextMonthAndPreviousMonthPreserveDate(): void
    {
        $date = Date::fromString('2024-01-31');
        $next = $date->nextMonth();
        $this->assertSame('2024-02-29', $next->format('Y-m-d'));

        $prev = Date::fromString('2024-03-31')->previousMonth();
        $this->assertSame('2024-02-29', $prev->format('Y-m-d'));
    }

    public function testAddDaysAndSameComparisons(): void
    {
        $date = Date::fromString('2024-02-01');
        $plus = $date->addDays(5);

        $this->assertSame('2024-02-06', $plus->format('Y-m-d'));
        $this->assertFalse($date->sameDay($plus));
        $this->assertTrue($date->sameMonth($plus));
    }

    public function testAgeAtCalculatesYears(): void
    {
        $birth = Date::fromString('2000-05-02');
        $base = Date::fromString('2026-05-01');

        $this->assertSame(25, $birth->ageAt($base));

        $base = Date::fromString('2026-05-02');
        $this->assertSame(26, $birth->ageAt($base));
    }

    public function testCalendarIndexReturnsLeadingNulls(): void
    {
        $date = Date::fromString('2024-09-01');

        $index = $date->calendarIndex(0);
        $this->assertSame(0, count(array_filter($index, fn ($item) => $item === null)));
        $this->assertSame(30, count(array_filter($index, fn ($item) => $item !== null)));
    }
}
