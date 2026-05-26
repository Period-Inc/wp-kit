<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface ProtectedAssetPathStrategyInterface
{
    public function publicPath(string $assetPath): string;

    public function protectedPath(string $assetPath): string;

    public function isProtected(string $path): bool;
}
