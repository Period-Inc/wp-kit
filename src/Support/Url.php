<?php

declare(strict_types=1);

namespace Period\WpFramework\Support;

final class Url
{
    public static function current(): string
    {
        $scheme = 'http';

        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        ) {
            $scheme = 'https';
        }

        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        if ($host === '') {
            return '';
        }

        return sprintf('%s://%s%s', $scheme, $host, $uri);
    }

    public static function root(): string
    {
        $current = self::current();
        if ($current === '') {
            return '';
        }

        $parts = parse_url($current);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $scheme = $parts['scheme'];
        $host = $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return sprintf('%s://%s%s/', $scheme, $host, $port);
    }

    public static function join(string $base, string $path): string
    {
        if ($path === '') {
            return $base;
        }

        $pathParts = parse_url($path);
        if ($pathParts !== false && isset($pathParts['scheme']) && in_array(strtolower($pathParts['scheme']), ['http', 'https'], true)) {
            return $path;
        }

        if (str_starts_with($path, '/')) {
            $root = self::root();
            if ($root === '') {
                return $path;
            }

            return rtrim($root, '/') . $path;
        }

        $baseParts = parse_url($base);
        if ($baseParts === false || empty($baseParts['scheme']) || empty($baseParts['host'])) {
            return $path;
        }

        $scheme = $baseParts['scheme'];
        $host = $baseParts['host'];
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';
        $basePath = $baseParts['path'] ?? '/';

        if (!str_ends_with($basePath, '/')) {
            $basePath = dirname($basePath);
        }

        if ($basePath === '\\' || $basePath === '.') {
            $basePath = '/';
        }

        $segments = array_filter(explode('/', $basePath), fn ($segment) => $segment !== '');
        $parts = array_filter(explode('/', $path), fn ($segment) => $segment !== '');

        foreach ($parts as $segment) {
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            if ($segment === '.') {
                continue;
            }

            $segments[] = $segment;
        }

        $resolvedPath = '/' . implode('/', $segments);

        return sprintf('%s://%s%s%s', $scheme, $host, $port, $resolvedPath);
    }

    public static function toPath(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['path'])) {
            return '';
        }

        $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($docRoot === '') {
            return '';
        }

        $path = $parts['path'];
        $path = '/' . ltrim($path, '/');

        return rtrim($docRoot, '/\\') . $path;
    }

    public static function relative(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return '';
        }

        $path = $parts['path'] ?? '';
        if ($path === '') {
            return '';
        }

        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path . $query;
    }
}
