<?php

declare(strict_types=1);

namespace Period\WpKit\Support;

final class Value
{
    public static function fallback(mixed $value, mixed $fallback, bool $considerZero = true): mixed
    {
        if ($considerZero && $value === 0) {
            return 0;
        }

        if (empty($value)) {
            return $fallback;
        }

        return $value;
    }
}
