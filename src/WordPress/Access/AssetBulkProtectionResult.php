<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetBulkProtectionResult
{
    /** @param array<int> $processedIds */
    private function __construct(
        private readonly string $action,
        private readonly array $processedIds,
        private readonly ?bool $protected,
    ) {}

    /** @param array<int> $processedIds */
    public static function protect(array $processedIds): self
    {
        return new self('period_protect_assets', $processedIds, true);
    }

    /** @param array<int> $processedIds */
    public static function unprotect(array $processedIds): self
    {
        return new self('period_unprotect_assets', $processedIds, false);
    }

    public static function noop(string $action): self
    {
        return new self($action, [], null);
    }

    public function action(): string { return $this->action; }

    /** @return array<int> */
    public function processedIds(): array { return $this->processedIds; }

    public function protected(): ?bool { return $this->protected; }
}
