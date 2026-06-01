<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class NativeFileStream implements FileStreamInterface
{
    public function exists(string $path): bool
    {
        return file_exists($path);
    }

    public function read(string $path): string
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new \RuntimeException('Failed to read file: ' . $path);
        }

        return $contents;
    }
}
