<?php

declare(strict_types=1);

namespace Period\WpFramework\Support;

final class CalendarDay
{
    public function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly int $day,
        public readonly int $weekday,
        public readonly bool $isCurrentMonth,
        public readonly bool $isToday,
    ) {}

    public function date(): string
    {
        return sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day);
    }
}
