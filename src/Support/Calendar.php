<?php

declare(strict_types=1);

namespace Period\WpKit\Support;

use Period\WpKit\Support\Locale\WeekdayName;

final class Calendar
{
    /**
     * Returns a two-dimensional array of CalendarDay objects for the given month.
     *
     * @return array<array<CalendarDay>>
     */
    public static function month(int $year, int $month, array $args = []): array
    {
        $startOfWeek = self::clampStartOfWeek((int) ($args['start_of_week'] ?? 0));

        $today       = self::today();
        $firstDay    = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = (int) date('t', $firstDay);

        // Weekday index of the 1st (0=Sun … 6=Sat)
        $firstWeekday = (int) date('w', $firstDay);

        // How many days from the previous month to prepend
        $leadDays  = (($firstWeekday - $startOfWeek) + 7) % 7;
        $totalDays = (int) ceil(($leadDays + $daysInMonth) / 7) * 7;

        $weeks = [];
        $week  = [];

        for ($i = 0; $i < $totalDays; $i++) {
            $ts  = mktime(0, 0, 0, $month, 1 - $leadDays + $i, $year);
            $d   = (int) date('j', $ts);
            $m   = (int) date('n', $ts);
            $y   = (int) date('Y', $ts);
            $dow = (int) date('w', $ts);

            $week[] = new CalendarDay(
                year:           $y,
                month:          $m,
                day:            $d,
                weekday:        $dow,
                isCurrentMonth: $m === $month && $y === $year,
                isToday:        $y === $today[0] && $m === $today[1] && $d === $today[2],
            );

            if (count($week) === 7) {
                $weeks[] = $week;
                $week    = [];
            }
        }

        return $weeks;
    }

    /**
     * Returns 7 weekday name strings starting from $startOfWeek.
     * Defaults to WeekdayName::EN_SHORT. Pass any 7-element array to localise.
     *
     * @param  string[]|null $names  Sunday-indexed names array (index 0 = Sunday)
     * @return string[]
     */
    public static function weekdays(int $startOfWeek = 0, ?array $names = null): array
    {
        $names       ??= WeekdayName::EN_SHORT;
        $startOfWeek   = self::clampStartOfWeek($startOfWeek);

        if ($startOfWeek > 0) {
            $names = array_merge(array_slice($names, $startOfWeek), array_slice($names, 0, $startOfWeek));
        }

        return array_values($names);
    }

    private static function clampStartOfWeek(int $value): int
    {
        return ($value >= 0 && $value <= 6) ? $value : 0;
    }

    /** @return array{int, int, int} */
    private static function today(): array
    {
        return [(int) date('Y'), (int) date('n'), (int) date('j')];
    }
}
