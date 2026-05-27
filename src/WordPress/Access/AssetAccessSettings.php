<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAccessSettings
{
    public const VISIBILITY_PUBLIC  = 'public';
    public const VISIBILITY_PRIVATE = 'private';

    /** @param string[] $protectedRoles */
    public function __construct(
        private readonly bool $enabled,
        private readonly array $protectedRoles,
        private readonly string $defaultVisibility,
        ?string $privateAssetRoot = null,
    ) {
        $this->privateAssetRoot = $this->normalizePrivateAssetRoot($privateAssetRoot);
    }

    private readonly ?string $privateAssetRoot;

    public static function default(): self
    {
        return new self(false, [], self::VISIBILITY_PUBLIC, null);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /** @return string[] */
    public function protectedRoles(): array
    {
        return $this->protectedRoles;
    }

    public function defaultVisibility(): string
    {
        return $this->defaultVisibility;
    }

    public function privateAssetRoot(): ?string
    {
        return $this->privateAssetRoot;
    }

    public function withEnabled(bool $enabled): self
    {
        return new self($enabled, $this->protectedRoles, $this->defaultVisibility, $this->privateAssetRoot);
    }

    /** @param string[] $protectedRoles */
    public function withProtectedRoles(array $protectedRoles): self
    {
        return new self($this->enabled, $protectedRoles, $this->defaultVisibility, $this->privateAssetRoot);
    }

    public function withDefaultVisibility(string $defaultVisibility): self
    {
        return new self($this->enabled, $this->protectedRoles, $defaultVisibility, $this->privateAssetRoot);
    }

    public function withPrivateAssetRoot(?string $privateAssetRoot): self
    {
        return new self($this->enabled, $this->protectedRoles, $this->defaultVisibility, $privateAssetRoot);
    }

    private function normalizePrivateAssetRoot(?string $privateAssetRoot): ?string
    {
        if ($privateAssetRoot === null) {
            return null;
        }

        $trimmed = trim($privateAssetRoot);

        return $trimmed === '' ? null : $trimmed;
    }
}
