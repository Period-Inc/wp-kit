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

    public static function charToHex(string $char, string $prefix = '\x'): string
    {
        if (preg_match('/^\\\\x[0-9a-fA-F]{2}$/', $char)) {
            return $char;
        }

        $hex = bin2hex($char);

        return $prefix . $hex;
    }

    public static function codepointToUtf8(int $codepoint): string
    {
        if ($codepoint < 0) {
            return '';
        }

        if (function_exists('mb_chr')) {
            $result = mb_chr($codepoint, self::UTF8);
            return $result !== false ? $result : '';
        }

        if ($codepoint < 0x80) {
            return chr($codepoint);
        }

        if ($codepoint < 0x800) {
            return chr(0xC0 | ($codepoint >> 6))
                 . chr(0x80 | ($codepoint & 0x3F));
        }

        if ($codepoint < 0x10000) {
            return chr(0xE0 | ($codepoint >> 12))
                 . chr(0x80 | (($codepoint >> 6) & 0x3F))
                 . chr(0x80 | ($codepoint & 0x3F));
        }

        return chr(0xF0 | ($codepoint >> 18))
             . chr(0x80 | (($codepoint >> 12) & 0x3F))
             . chr(0x80 | (($codepoint >> 6) & 0x3F))
             . chr(0x80 | ($codepoint & 0x3F));
    }
}
