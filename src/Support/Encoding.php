<?php

declare(strict_types=1);

namespace Period\WpFramework\Support;

final class Encoding
{
    public const UTF8 = 'UTF-8';

    public static function base64UrlEncode(string $value): string
    {
        $encoded = base64_encode($value);
        if ($encoded === false) {
            return '';
        }

        return strtr($encoded, '+/=', '_-.');
    }

    public static function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '_-.', '+/='), true);

        return $decoded === false ? '' : $decoded;
    }

    public static function decodeHtmlEntities(string $value, int $flags = ENT_COMPAT, string $encoding = self::UTF8): string
    {
        return html_entity_decode($value, $flags, $encoding);
    }
}
