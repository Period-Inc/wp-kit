<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface AssetStorageInterface
{
    public function find(string $assetPath): ?AssetStorageItem;
}
