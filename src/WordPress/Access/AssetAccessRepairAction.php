<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessRepairAction
{
    public const TYPE_CREATE_DIRECTORY = 'create_directory';
    public const TYPE_PERMISSION_WARNING = 'permission_warning';
    public const TYPE_READABILITY_WARNING = 'readability_warning';

    public function __construct(
        private readonly string $type,
        private readonly string $path,
        private readonly string $message,
    ) {}

    public static function createDirectory(string $path): self
    {
        return new self(
            self::TYPE_CREATE_DIRECTORY,
            $path,
            'private asset root directory should be created',
        );
    }

    public static function permissionWarning(string $path): self
    {
        return new self(
            self::TYPE_PERMISSION_WARNING,
            $path,
            'private asset root is not writable',
        );
    }

    public static function readabilityWarning(string $path): self
    {
        return new self(
            self::TYPE_READABILITY_WARNING,
            $path,
            'private asset root is not readable',
        );
    }

    public function type(): string
    {
        return $this->type;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function message(): string
    {
        return $this->message;
    }
}
