<?php

declare(strict_types=1);

namespace Period\WpFramework\Support;

final class ArrayUtil
{
    public static function get(array $array, string|int $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $array) ? $array[$key] : $default;
    }

    public static function flatten(array $array): array
    {
        $result = [];

        foreach ($array as $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flatten($value));
                continue;
            }

            $result[] = $value;
        }

        return $result;
    }

    public static function isEmptyValues(array $array, bool $recursive = false): bool
    {
        foreach ($array as $value) {
            if ($recursive && is_array($value)) {
                if (!self::isEmptyValues($value, true)) {
                    return false;
                }
                continue;
            }

            if (!empty($value)) {
                return false;
            }
        }

        return true;
    }

    public static function makeAssociative(array $keys, array $values = []): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = array_key_exists($key, $values) ? $values[$key] : null;
        }

        return $result;
    }

    public static function isList(array $array): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }

        if ($array === []) {
            return true;
        }

        return array_keys($array) === range(0, count($array) - 1);
    }

    public static function isAssociative(array $array): bool
    {
        return !self::isList($array);
    }
}
