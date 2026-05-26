<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetStorageItemFactory
{
    /** @param array<string, mixed> $data */
    public function fromArray(array $data): AssetStorageItem
    {
        if (!isset($data['path']) || !is_string($data['path']) || $data['path'] === '') {
            throw new \InvalidArgumentException('AssetStorageItem requires a non-empty string path');
        }

        return new AssetStorageItem(
            path: $data['path'],
            url: isset($data['url']) ? (string) $data['url'] : null,
            mimeType: isset($data['mimeType']) ? (string) $data['mimeType'] : null,
            size: isset($data['size']) ? (int) $data['size'] : null,
            lastModified: $this->normalizeDateTime($data['lastModified'] ?? null),
            metadata: isset($data['metadata']) && is_array($data['metadata']) ? $data['metadata'] : [],
        );
    }

    private function normalizeDateTime(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($value);
        }

        if (is_int($value)) {
            return (new \DateTimeImmutable('@' . $value));
        }

        if (is_string($value)) {
            return new \DateTimeImmutable($value);
        }

        return null;
    }
}
