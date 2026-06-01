<?php

declare(strict_types=1);

namespace Period\WpKit\Support;

final class ImageUtil
{
    public static function ratio(int $width, int $height): float
    {
        if ($height <= 0) {
            return 0.0;
        }

        return $width / $height;
    }

    public static function aspectRatio(int $width, int $height): string
    {
        if ($width <= 0 || $height <= 0) {
            return '';
        }

        $gcd = self::gcd($width, $height);
        if ($gcd === 0) {
            return '';
        }

        return sprintf('%d:%d', $width / $gcd, $height / $gcd);
    }

    public static function orientation(int $width, int $height): string
    {
        if ($width <= 0 || $height <= 0) {
            return 'unknown';
        }

        if ($width > $height) {
            return 'landscape';
        }

        if ($width < $height) {
            return 'portrait';
        }

        return 'square';
    }

    public static function paddingTopPercent(int $width, int $height): float
    {
        if ($width <= 0) {
            return 0.0;
        }

        return round($height / $width * 100, 4);
    }

    public static function normalizeSize(int $width, int $height): array
    {
        return [
            'width' => $width < 0 ? 0 : $width,
            'height' => $height < 0 ? 0 : $height,
        ];
    }

    private static function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);

        if ($a === 0) {
            return $b;
        }

        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return $a;
    }
}
