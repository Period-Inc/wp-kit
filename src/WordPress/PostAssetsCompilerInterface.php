<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

interface PostAssetsCompilerInterface
{
    public function compile(string $source): PostAssetsCompileResult;
}
