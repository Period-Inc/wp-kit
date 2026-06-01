<?php

declare(strict_types=1);

namespace Period\WpKit\Support;

final class Url
{
    public static function current(?array $server = null): string
    {
        $server ??= $_SERVER;

        $host = $server['HTTP_HOST'] ?? $server['SERVER_NAME'] ?? '';
        if (!is_string($host) || $host === '') {
            return '';
        }

        $scheme = 'http';
        $https = $server['HTTPS'] ?? '';
        $port = $server['SERVER_PORT'] ?? '';
        $forwardedProto = $server['HTTP_X_FORWARDED_PROTO'] ?? '';

        if ((is_string($https) && strtolower($https) !== 'off' && $https !== '')
            || (string) $port === '443'
            || (is_string($forwardedProto) && strtolower($forwardedProto) === 'https')
        ) {
            $scheme = 'https';
        }

        $uri = $server['REQUEST_URI'] ?? '/';
        if (!is_string($uri) || $uri === '') {
            $uri = '/';
        }

        return sprintf('%s://%s%s', $scheme, $host, $uri);
    }

    public static function root(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        $scheme = (string) $parts['scheme'];
        $host = (string) $parts['host'];
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return sprintf('%s://%s%s', $scheme, $host, $port);
    }

    public static function join(string $base, string $path): string
    {
        if ($path === '') {
            return $base;
        }

        if (str_starts_with($path, '//')) {
            $baseParts = parse_url($base);
            if ($baseParts === false || empty($baseParts['scheme'])) {
                return $path;
            }

            return $baseParts['scheme'] . ':' . $path;
        }

        $pathParts = parse_url($path);
        if ($pathParts !== false && isset($pathParts['scheme']) && in_array(strtolower((string) $pathParts['scheme']), ['http', 'https'], true)) {
            return $path;
        }

        $baseParts = parse_url($base);
        if ($baseParts === false || empty($baseParts['scheme']) || empty($baseParts['host'])) {
            return $path;
        }

        $scheme = (string) $baseParts['scheme'];
        $host = (string) $baseParts['host'];
        $port = isset($baseParts['port']) ? ':' . $baseParts['port'] : '';

        if (str_starts_with($path, '/')) {
            $query = isset($pathParts['query']) ? '?' . $pathParts['query'] : '';
            $fragment = isset($pathParts['fragment']) ? '#' . $pathParts['fragment'] : '';
            return sprintf('%s://%s%s%s%s%s', $scheme, $host, $port, $path, $query, $fragment);
        }

        $basePath = $baseParts['path'] ?? '/';
        if (!str_ends_with($basePath, '/')) {
            $basePath .= '/';
        }

        if ($basePath === '\\' || $basePath === '.') {
            $basePath = '/';
        }

        $targetPath = $pathParts['path'] ?? $path;
        $resolvedPath = self::normalizePath($basePath . $targetPath);
        $query = isset($pathParts['query']) ? '?' . $pathParts['query'] : '';
        $fragment = isset($pathParts['fragment']) ? '#' . $pathParts['fragment'] : '';

        return sprintf('%s://%s%s%s%s%s', $scheme, $host, $port, $resolvedPath, $query, $fragment);
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
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        return $path . $query . $fragment;
    }

    public static function toPath(string $url, string $documentRoot): string
    {
        if ($documentRoot === '') {
            return '';
        }

        $relative = self::relative($url);
        if ($relative === '') {
            return '';
        }

        $path = parse_url($relative, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return '';
        }

        return rtrim($documentRoot, '/\\') . '/' . ltrim($path, '/');
    }

    private static function normalizePath(string $path): string
    {
        if ($path === '') {
            return '/';
        }

        $isAbsolute = str_starts_with($path, '/');
        $segments = [];

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        $normalized = implode('/', $segments);

        return ($isAbsolute ? '/' : '') . $normalized;
    }
}
