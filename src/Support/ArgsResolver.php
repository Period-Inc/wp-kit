<?php

declare(strict_types=1);

namespace Period\WpFramework\Support;

final class ArgsResolver
{
    public function resolve(array $defaults, array $args = [], string $mode = 'deep'): array
    {
        if ($mode === 'shallow') {
            return array_merge($defaults, $args);
        }

        if ($mode !== 'deep') {
            $mode = 'deep';
        }

        foreach ($args as $key => $value) {
            if (
                array_key_exists($key, $defaults)
                && is_array($defaults[$key])
                && is_array($value)
                && $this->isAssociative($defaults[$key])
                && $this->isAssociative($value)
            ) {
                $defaults[$key] = $this->resolve($defaults[$key], $value, 'deep');
                continue;
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }

    private function isAssociative(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
