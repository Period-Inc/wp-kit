<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AttachmentAssetStorage implements AssetStorageInterface
{
    /** @var callable(string): ?array<string, mixed> */
    private readonly mixed $attachmentResolver;

    /** @param callable(string): ?array<string, mixed> $attachmentResolver */
    public function __construct(callable $attachmentResolver)
    {
        $this->attachmentResolver = $attachmentResolver;
    }

    public function find(string $assetPath): ?AssetStorageItem
    {
        $data = ($this->attachmentResolver)($assetPath);

        if ($data === null) {
            return null;
        }

        return (new AssetStorageItemFactory())->fromArray($data);
    }
}
