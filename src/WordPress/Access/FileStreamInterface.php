<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface FileStreamInterface
{
    public function exists(string $path): bool;

    /** @throws \RuntimeException if the file cannot be read */
    public function read(string $path): string;
}
