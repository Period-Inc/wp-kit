<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetFileMoveResult
{
    private function __construct(
        private readonly bool $success,
        private readonly string $from,
        private readonly string $to,
        private readonly ?string $error,
    ) {}

    public static function success(string $from, string $to): self
    {
        return new self(true, $from, $to, null);
    }

    public static function failure(string $from, string $to, string $error): self
    {
        return new self(false, $from, $to, $error);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function from(): string
    {
        return $this->from;
    }

    public function to(): string
    {
        return $this->to;
    }

    public function error(): ?string
    {
        return $this->error;
    }
}
