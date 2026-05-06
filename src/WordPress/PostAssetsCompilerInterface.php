<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

interface PostAssetsCompilerInterface
{
    public function compile(string $source): PostAssetsCompileResult;
}
