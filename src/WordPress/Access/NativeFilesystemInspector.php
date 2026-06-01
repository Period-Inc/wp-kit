<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class NativeFilesystemInspector implements FilesystemInspectorInterface
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function isReadable(string $path): bool
    {
        return is_readable($path);
    }

    public function isWritable(string $path): bool
    {
        return is_writable($path);
    }
}
