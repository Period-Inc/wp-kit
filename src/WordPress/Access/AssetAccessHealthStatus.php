<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessHealthStatus
{
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_ERROR = 'error';

    private function __construct(
        private readonly bool $healthy,
        private readonly string $code,
        private readonly string $message,
        private readonly string $severity,
    ) {}

    public static function info(string $code, string $message, bool $healthy = true): self
    {
        return new self($healthy, $code, $message, self::SEVERITY_INFO);
    }

    public static function warning(string $code, string $message, bool $healthy = false): self
    {
        return new self($healthy, $code, $message, self::SEVERITY_WARNING);
    }

    public static function error(string $code, string $message, bool $healthy = false): self
    {
        return new self($healthy, $code, $message, self::SEVERITY_ERROR);
    }

    public function healthy(): bool
    {
        return $this->healthy;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function severity(): string
    {
        return $this->severity;
    }
}
