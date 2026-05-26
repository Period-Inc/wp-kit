<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetUploadPathResolver
{
    public function __construct(
        private readonly ProtectedAssetPathStrategyInterface $strategy,
    ) {}

    public function resolve(string $assetPath, bool $protected): string
    {
        if ($protected) {
            return $this->strategy->protectedPath($assetPath);
        }

        return $this->strategy->publicPath($assetPath);
    }
}
