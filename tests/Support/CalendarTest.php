<?php

declare(strict_types=1);

namespace Period\WpKit\Tests\Support;

use PHPUnit\Framework\TestCase;
use Period\WpKit\Support\Calendar;
use Period\WpKit\Support\CalendarDay;
use Period\WpKit\Support\Locale\WeekdayName;

final class CalendarTest extends TestCase
{
    // 2026-05-01 is a Friday (weekday 5)

    public function testMonthReturnsTwoDimensionalArray(): void
    {
        $weeks = Calendar::month(2026, 5);

        $this->assertIsArray($weeks);
        $this->assertNotEmpty($weeks);
        foreach ($weeks as $week) {
            $this->assertIsArray($week);
        }
    }

    public function testEachWeekHasSevenDays(): void
    {
        foreach (Calendar::month(2026, 5) as $week) {
            $this->assertCount(7, $week);
        }
    }

    public function testSundayStartFirstDayIsSunday(): void
    {
        $weeks = Calendar::month(2026, 5, ['start_of_week' => 0]);
        $first = $weeks[0][0];

        // 2026-05-01 is Friday; the grid starts on 2026-04-26 (Sunday)
        $this->assertSame(0, $first->weekday); // Sunday
        $this->assertSame('2026-04-26', $first->date());
    }

    public function testMondayStartFirstDayIsMonday(): void
    {
        $weeks = Calendar::month(2026, 5, ['start_of_week' => 1]);
        $first = $weeks[0][0];

        // 2026-05-01 is Friday; the grid starts on 2026-04-27 (Monday)
        $this->assertSame(1, $first->weekday); // Monday
        $this->assertSame('2026-04-27', $first->date());
    }

    public function testPreviousMonthDaysArePrepended(): void
    {
        $weeks = Calendar::month(2026, 5, ['start_of_week' => 0]);
        $first = $weeks[0][0];

        $this->assertFalse($first->isCurrentMonth);
        $this->assertSame(4, $first->month); // April
    }

    public function testNextMonthDaysAreAppended(): void
    {
        $weeks  = Calendar::month(2026, 5, ['start_of_week' => 0]);
        $last   = end($weeks);
        $lastDay = end($last);

        $this->assertFalse($lastDay->isCurrentMonth);
        $this->assertSame(6, $lastDay->month); // June
    }

    public function testIsCurrentMonthIsCorrect(): void
    {
        $weeks = Calendar::month(2026, 5);

        foreach ($weeks as $week) {
            foreach ($week as $day) {
                $expected = $day->month === 5 && $day->year === 2026;
                $this->assertSame($expected, $day->isCurrentMonth, "Failed for {$day->date()}");
            }
        }
    }

    public function testAllMayDaysArePresent(): void
    {
        $weeks = Calendar::month(2026, 5);

        $mayDays = [];
        foreach ($weeks as $week) {
            foreach ($week as $day) {
                if ($day->isCurrentMonth) {
                    $mayDays[] = $day->day;
                }
            }
        }

        $this->assertSame(range(1, 31), $mayDays);
    }

    public function testWeekdaysSundayStart(): void
    {
        $this->assertSame(
            ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            Calendar::weekdays(0)
        );
    }

    public function testWeekdaysMondayStart(): void
    {
        $this->assertSame(
            ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            Calendar::weekdays(1)
        );
    }

    public function testWeekdaysSaturdayStart(): void
    {
        $this->assertSame(
            ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
            Calendar::weekdays(6)
        );
    }

    public function testCalendarDayDateFormat(): void
    {
        $day = new CalendarDay(2026, 5, 1, 5, true, false);

        $this->assertSame('2026-05-01', $day->date());
    }

    public function testCalendarDayDateFormatSingleDigits(): void
    {
        $day = new CalendarDay(2026, 1, 3, 6, true, false);

        $this->assertSame('2026-01-03', $day->date());
    }

    public function testWeekdaysDefaultMatchesEnShort(): void
    {
        $this->assertSame(WeekdayName::EN_SHORT, Calendar::weekdays(0));
    }

    public function testWeekdaysCustomNamesAreUsed(): void
    {
        $result = Calendar::weekdays(0, WeekdayName::JA_SHORT);

        $this->assertSame(WeekdayName::JA_SHORT, $result);
    }

    public function testWeekdaysCustomNamesMondayStart(): void
    {
        $result = Calendar::weekdays(1, WeekdayName::JA_SHORT);

        // JA_SHORT = ['日','月','火','水','木','金','土'], Monday start → ['月','火','水','木','金','土','日']
        $this->assertSame(['月', '火', '水', '木', '金', '土', '日'], $result);
    }

    public function testWeekdaysOutOfRangeStartOfWeekIsClampedToZero(): void
    {
        $this->assertSame(Calendar::weekdays(0), Calendar::weekdays(7));
        $this->assertSame(Calendar::weekdays(0), Calendar::weekdays(-1));
    }

    public function testIsTodayIsCorrectForToday(): void
    {
        $y = (int) date('Y');
        $m = (int) date('n');

        $weeks = Calendar::month($y, $m);

        $todayCount = 0;
        foreach ($weeks as $week) {
            foreach ($week as $day) {
                if ($day->isToday) {
                    $todayCount++;
                    $this->assertSame(date('Y-m-d'), $day->date());
                }
            }
        }

        $this->assertSame(1, $todayCount);
    }
}
