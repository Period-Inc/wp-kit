<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class DefaultProtectedAssetPathStrategy implements ProtectedAssetPathStrategyInterface
{
    private const PUBLIC_PREFIX    = '/uploads/';
    private const PROTECTED_PREFIX = '/protected-uploads/';

    public function protectedPath(string $assetPath): string
    {
        if (str_starts_with($assetPath, self::PUBLIC_PREFIX)) {
            return self::PROTECTED_PREFIX . substr($assetPath, strlen(self::PUBLIC_PREFIX));
        }

        return self::PROTECTED_PREFIX . ltrim($assetPath, '/');
    }

    public function publicPath(string $assetPath): string
    {
        if (str_starts_with($assetPath, self::PROTECTED_PREFIX)) {
            return self::PUBLIC_PREFIX . substr($assetPath, strlen(self::PROTECTED_PREFIX));
        }

        return $assetPath;
    }

    public function isProtected(string $path): bool
    {
        return str_starts_with($path, self::PROTECTED_PREFIX);
    }
}
