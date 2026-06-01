<?php

declare(strict_types=1);

namespace Period\WpKit\Support;

use DateTimeImmutable;

final class Date
{
    private DateTimeImmutable $date;

    public function __construct(?string $date = null)
    {
        $dateString = $date ?? 'now';
        $this->date = new DateTimeImmutable($dateString);
        $this->date = $this->date->setTime(0, 0, 0);
    }

    public static function today(): self
    {
        return new self('today');
    }

    public static function fromString(string $date): self
    {
        return new self($date);
    }

    public function year(): int
    {
        return (int) $this->date->format('Y');
    }

    public function month(): int
    {
        return (int) $this->date->format('n');
    }

    public function day(): int
    {
        return (int) $this->date->format('j');
    }

    public function format(string $format): string
    {
        return $this->date->format($format);
    }

    public function daysInMonth(): int
    {
        return (int) $this->date->format('t');
    }

    public function isLeapYear(): bool
    {
        $year = $this->year();

        return ($year % 4 === 0 && $year % 100 !== 0) || ($year % 400 === 0);
    }

    public function weekday(): int
    {
        return (int) $this->date->format('w');
    }

    public function nextMonth(): self
    {
        $year = $this->year();
        $month = $this->month();
        $day = $this->day();

        $month++;
        if ($month > 12) {
            $month = 1;
            $year++;
        }

        $targetDays = $this->daysInMonthFor($year, $month);
        $targetDay = min($day, $targetDays);

        return new self(sprintf('%04d-%02d-%02d', $year, $month, $targetDay));
    }

    public function previousMonth(): self
    {
        $year = $this->year();
        $month = $this->month();
        $day = $this->day();

        $month--;
        if ($month < 1) {
            $month = 12;
            $year--;
        }

        $targetDays = $this->daysInMonthFor($year, $month);
        $targetDay = min($day, $targetDays);

        return new self(sprintf('%04d-%02d-%02d', $year, $month, $targetDay));
    }

    private function daysInMonthFor(int $year, int $month): int
    {
        return (int) (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t');
    }

    public function addDays(int $days): self
    {
        if ($days === 0) {
            return new self($this->format('Y-m-d'));
        }

        $modifier = sprintf('%+d days', $days);
        return new self($this->date->modify($modifier)->format('Y-m-d'));
    }

    public function sameDay(self $other): bool
    {
        return $this->format('Y-m-d') === $other->format('Y-m-d');
    }

    public function sameMonth(self $other): bool
    {
        return $this->year() === $other->year() && $this->month() === $other->month();
    }

    public function ageAt(?self $base = null): int
    {
        $base = $base ?? self::today();

        $age = $base->year() - $this->year();

        if ($base->month() < $this->month() || ($base->month() === $this->month() && $base->day() < $this->day())) {
            $age--;
        }

        return $age;
    }

    public function calendarIndex(int $startOfWeek = 0): array
    {
        $startOfWeek = $startOfWeek % 7;
        $offset = ($this->weekday() - $startOfWeek + 7) % 7;
        $index = array_fill(0, $offset, null);

        for ($day = 1, $last = $this->daysInMonth(); $day <= $last; $day++) {
            $index[] = $day;
        }

        return $index;
    }
}
