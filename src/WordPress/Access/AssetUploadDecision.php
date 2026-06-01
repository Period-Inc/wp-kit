<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetUploadDecision
{
    private function __construct(
        private readonly bool $protected,
        private readonly string $targetPath,
    ) {}

    public static function asPublic(string $targetPath): self
    {
        return new self(false, $targetPath);
    }

    public static function asProtected(string $targetPath): self
    {
        return new self(true, $targetPath);
    }

    public function isProtected(): bool
    {
        return $this->protected;
    }

    public function targetPath(): string
    {
        return $this->targetPath;
    }
}
