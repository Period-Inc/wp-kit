<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class NativeFilesystemOperator implements FilesystemOperatorInterface
{
    public function createDirectory(string $path): bool
    {
        return mkdir($path);
    }

    public function setPermissions(string $path, int $mode): bool
    {
        return chmod($path, $mode);
    }
}
