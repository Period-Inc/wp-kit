<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface FilesystemInspectorInterface
{
    public function exists(string $path): bool;

    public function isReadable(string $path): bool;

    public function isWritable(string $path): bool;
}
