<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

final class PostAssetsCompileService
{
    public function __construct(
        private readonly PostMetaManager $meta,
        private readonly PostAssetsCompilerInterface $compiler,
    ) {}

    public function compileCss(
        int $postId,
        string $source,
    ): PostAssetsCompileResult {
        $result = $this->compiler->compile($source);

        if ($result->success) {
            $this->meta->update(
                $postId,
                'csscode_compiled',
                $result->compiled
            );

            $this->meta->update(
                $postId,
                'csscode_last_compile_error',
                ''
            );

            $this->meta->update(
                $postId,
                'csscode_last_compiled_at',
                gmdate('c')
            );

            return $result;
        }

        $this->meta->update(
            $postId,
            'csscode_last_compile_error',
            $result->error ?? 'Unknown compile error'
        );

        return $result;
    }
}
