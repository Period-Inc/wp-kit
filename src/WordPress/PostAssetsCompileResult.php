<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

final class PostAssetsCompileResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $compiled,
        public readonly ?string $error = null,
    ) {}
}
