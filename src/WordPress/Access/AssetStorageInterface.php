<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface AssetStorageInterface
{
    public function find(string $assetPath): ?AssetStorageItem;
}
