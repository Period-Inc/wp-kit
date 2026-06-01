<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAttachmentMeta
{
    public function __construct(
        private readonly bool $protected,
        private readonly ?string $protectedPath,
        private readonly ?string $deliveryUrl,
    ) {}

    public function isProtected(): bool
    {
        return $this->protected;
    }

    public function protectedPath(): ?string
    {
        return $this->protectedPath;
    }

    public function deliveryUrl(): ?string
    {
        return $this->deliveryUrl;
    }
}
