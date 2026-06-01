<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

final class PostAssetsRenderer
{
    public function __construct(
        private readonly PostAssets $assets,
        private readonly PostMetaManager $meta,
        private readonly ScriptStyleRegistrar $registrar,
    ) {}

    public function render(int $postId): void
    {
        $this->renderCss($postId);
        $this->renderJs($postId);
    }

    public function renderCss(int $postId): void
    {
        $cssFile = $this->assets->cssFile($postId);

        if ($cssFile !== '') {
            $handle = $this->handle($postId, 'css-file');

            $this->registrar->style(
                $handle,
                $cssFile,
                ['enqueue' => true]
            );
        }

        $cssCode = $this->compiledCss($postId);

        if ($cssCode !== '') {
            $handle = $this->handle($postId, 'css-inline');

            $this->registrar->style(
                $handle,
                false,
                ['enqueue' => true]
            );

            $this->registrar->inlineStyle(
                $handle,
                $cssCode
            );
        }
    }

    public function renderJs(int $postId): void
    {
        $jsFile = $this->assets->jsFile($postId);

        if ($jsFile !== '') {
            $handle = $this->handle($postId, 'js-file');

            $this->registrar->script(
                $handle,
                $jsFile,
                ['enqueue' => true]
            );
        }

        $jsCode = $this->assets->jsCode($postId);

        if ($jsCode !== '') {
            $handle = $this->handle($postId, 'js-inline');

            $this->registrar->script(
                $handle,
                '',
                ['enqueue' => true]
            );

            $this->registrar->inlineScript(
                $handle,
                $jsCode
            );
        }
    }

    private function compiledCss(int $postId): string
    {
        $compiled = $this->meta->get(
            $postId,
            'csscode_minified'
        );

        if (is_string($compiled) && trim($compiled) !== '') {
            return trim($compiled);
        }

        $compiled = $this->meta->get(
            $postId,
            'csscode_compiled'
        );

        return is_string($compiled)
            ? trim($compiled)
            : '';
    }

    private function handle(int $postId, string $suffix): string
    {
        return 'post-assets-' . $postId . '-' . $suffix;
    }
}
