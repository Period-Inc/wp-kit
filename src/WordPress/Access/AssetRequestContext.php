<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetRequestContext
{
    /**
     * @param string[] $currentUserRoles
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private readonly string $assetPath,
        private readonly string $assetUrl,
        private readonly int $currentUserId,
        private readonly array $currentUserRoles,
        private readonly \DateTimeImmutable $requestTime,
        private readonly array $metadata = [],
    ) {}

    public function assetPath(): string
    {
        return $this->assetPath;
    }

    public function assetUrl(): string
    {
        return $this->assetUrl;
    }

    public function currentUserId(): int
    {
        return $this->currentUserId;
    }

    /** @return string[] */
    public function currentUserRoles(): array
    {
        return $this->currentUserRoles;
    }

    public function requestTime(): \DateTimeImmutable
    {
        return $this->requestTime;
    }

    /** @return array<string, mixed> */
    public function metadata(): array
    {
        return $this->metadata;
    }

    /** @param array<string, mixed> $metadata */
    public function withMetadata(array $metadata): self
    {
        return new self(
            $this->assetPath,
            $this->assetUrl,
            $this->currentUserId,
            $this->currentUserRoles,
            $this->requestTime,
            $metadata,
        );
    }
}
