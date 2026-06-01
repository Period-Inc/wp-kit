<?php

declare(strict_types=1);

namespace Period\WpKit\Support;

final class StringUtil
{
    public static function quote(string $value, string $quote = '"'): string
    {
        $escaped = str_replace($quote, '\\' . $quote, $value);

        return $quote . $escaped . $quote;
    }

    public static function indent(string $value, string $indent = "\t"): string
    {
        if ($value === '') {
            return '';
        }

        $lines = explode("\n", $value);
        foreach ($lines as &$line) {
            $line = $indent . $line;
        }

        return implode("\n", $lines);
    }

    public static function words(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        return preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    public static function toJsArray(array $values): string
    {
        return json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
