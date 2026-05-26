<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetStorageItem
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        private readonly string $path,
        private readonly ?string $url,
        private readonly ?string $mimeType,
        private readonly ?int $size,
        private readonly ?\DateTimeImmutable $lastModified,
        private readonly array $metadata = [],
    ) {}

    public function path(): string
    {
        return $this->path;
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function mimeType(): ?string
    {
        return $this->mimeType;
    }

    public function size(): ?int
    {
        return $this->size;
    }

    public function lastModified(): ?\DateTimeImmutable
    {
        return $this->lastModified;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
