<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class NativePhpHttpEmitter implements HttpEmitterInterface
{
    public function status(int $code): void
    {
        http_response_code($code);
    }

    public function header(string $name, string $value): void
    {
        header($name . ': ' . $value);
    }

    public function body(?string $body): void
    {
        if ($body !== null) {
            echo $body;
        }
    }

    public function redirect(string $url, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
    }
}
