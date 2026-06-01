<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class SignedAssetToken
{
    public function __construct(
        private readonly string $assetPath,
        private readonly \DateTimeImmutable $expiresAt,
        private readonly string $signature,
    ) {}

    public function assetPath(): string
    {
        return $this->assetPath;
    }

    public function expiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function signature(): string
    {
        return $this->signature;
    }
}
