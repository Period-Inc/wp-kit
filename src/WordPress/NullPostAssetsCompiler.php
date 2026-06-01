<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

final class NullPostAssetsCompiler implements PostAssetsCompilerInterface
{
    public function compile(string $source): PostAssetsCompileResult
    {
        return new PostAssetsCompileResult(
            success: true,
            compiled: trim($source),
        );
    }
}
