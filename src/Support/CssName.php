<?php

declare(strict_types=1);

namespace Period\WpFramework\Support;

final class CssName
{
    public static function fromString(string $value, string $prefix = ''): string
    {
        $value = trim($value);

        if ($value === '') {
            return self::fallback($prefix);
        }

        // UTF-8 → rawurlencode
        $value = rawurlencode($value);

        // 変換マップ
        $map = [
            ' '  => '--',
            '/'  => '--',
            '\\' => '--',
            '&'  => '--',

            '.'  => '_',

            '%'  => '_x',

            '?'  => '___',
            '#'  => '____',

            '='  => '-',
        ];

        $value = strtr($value, $map);

        // 許可文字以外は "-"
        $value = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $value);

        // 先頭末尾整理
        $value = trim($value, '-_');

        if ($value === '') {
            return self::fallback($prefix);
        }

        // prefix適用
        if ($prefix !== '') {
            return $prefix . '-' . $value;
        }

        // 数字始まり対策
        if (preg_match('/^[0-9]/', $value)) {
            return 'cssname-' . $value;
        }

        return $value;
    }

    public static function fromUrl(string $url, string $prefix = ''): string
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return self::fromString($url, $prefix);
        }

        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        $query = $parts['query'] ?? '';
        $fragment = $parts['fragment'] ?? '';

        $combined = $host;

        if ($path !== '') {
            $combined .= '/' . ltrim($path, '/');
        }

        if ($query !== '') {
            $combined .= '?' . $query;
        }

        if ($fragment !== '') {
            $combined .= '#' . $fragment;
        }

        return self::fromString($combined, $prefix);
    }

    private static function fallback(string $prefix): string
    {
        return $prefix !== '' ? $prefix : 'cssname';
    }
}
