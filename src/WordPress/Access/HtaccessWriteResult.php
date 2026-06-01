<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class HtaccessWriteResult
{
    private function __construct(
        private readonly bool $success,
        private readonly string $path,
        private readonly bool $written,
        private readonly bool $backupCreated,
        private readonly ?string $error,
    ) {}

    public static function success(string $path, bool $written, bool $backupCreated): self
    {
        return new self(true, $path, $written, $backupCreated, null);
    }

    public static function failure(string $path, string $error): self
    {
        return new self(false, $path, false, false, $error);
    }

    public function isSuccess(): bool { return $this->success; }
    public function path(): string { return $this->path; }
    public function written(): bool { return $this->written; }
    public function backupCreated(): bool { return $this->backupCreated; }
    public function error(): ?string { return $this->error; }
}
