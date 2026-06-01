<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

use ScssPhp\ScssPhp\Compiler;
use Throwable;

final class ScssPhpPostAssetsCompiler implements PostAssetsCompilerInterface
{
    public function __construct(
        private readonly Compiler $compiler = new Compiler(),
    ) {}

    public function compile(string $source): PostAssetsCompileResult
    {
        $source = trim($source);

        if ($source === '') {
            return new PostAssetsCompileResult(true, '');
        }

        try {
            $result = $this->compiler->compileString($source);

            return new PostAssetsCompileResult(
                true,
                method_exists($result, 'getCss')
                    ? trim($result->getCss())
                    : trim((string) $result)
            );
        } catch (Throwable $e) {
            return new PostAssetsCompileResult(
                false,
                '',
                $e->getMessage()
            );
        }
    }
}
