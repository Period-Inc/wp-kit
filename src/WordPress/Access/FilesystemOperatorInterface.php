<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface FilesystemOperatorInterface
{
    public function createDirectory(string $path): bool;

    public function setPermissions(string $path, int $mode): bool;
}
