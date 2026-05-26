<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class DirectAccessProtectionStrategy
{
    public const MODE_REWRITE = 'rewrite';
    public const MODE_DENY = 'deny';
    public const MODE_OUTSIDE_WEBROOT = 'outside_webroot';

    private function __construct(private readonly string $mode) {}

    public static function rewrite(): self
    {
        return new self(self::MODE_REWRITE);
    }

    public static function deny(): self
    {
        return new self(self::MODE_DENY);
    }

    public static function outsideWebroot(): self
    {
        return new self(self::MODE_OUTSIDE_WEBROOT);
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function isRewrite(): bool
    {
        return $this->mode === self::MODE_REWRITE;
    }

    public function isDeny(): bool
    {
        return $this->mode === self::MODE_DENY;
    }

    public function isOutsideWebroot(): bool
    {
        return $this->mode === self::MODE_OUTSIDE_WEBROOT;
    }
}
