<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface HttpEmitterInterface
{
    public function status(int $code): void;

    public function header(string $name, string $value): void;

    public function body(?string $body): void;

    public function redirect(string $url, int $statusCode): void;
}
