<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class InMemoryAssetStorage implements AssetStorageInterface
{
    /** @param AssetStorageItem[] $items */
    public function __construct(
        private readonly array $items,
    ) {}

    public function find(string $assetPath): ?AssetStorageItem
    {
        foreach ($this->items as $item) {
            if ($item->path() === $assetPath) {
                return $item;
            }
        }

        return null;
    }
}
