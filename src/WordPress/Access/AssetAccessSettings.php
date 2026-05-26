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
    ) {}

    public static function default(): self
    {
        return new self(false, [], self::VISIBILITY_PUBLIC);
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

    public function withEnabled(bool $enabled): self
    {
        return new self($enabled, $this->protectedRoles, $this->defaultVisibility);
    }

    /** @param string[] $protectedRoles */
    public function withProtectedRoles(array $protectedRoles): self
    {
        return new self($this->enabled, $protectedRoles, $this->defaultVisibility);
    }

    public function withDefaultVisibility(string $defaultVisibility): self
    {
        return new self($this->enabled, $this->protectedRoles, $defaultVisibility);
    }
}
