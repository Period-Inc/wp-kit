<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAccessResult
{
    private function __construct(
        private readonly bool $allowed,
        private readonly ?string $reason,
    ) {}

    public static function allow(): self
    {
        return new self(true, null);
    }

    public static function deny(string $reason): self
    {
        return new self(false, $reason);
    }

    public function allowed(): bool
    {
        return $this->allowed;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}
