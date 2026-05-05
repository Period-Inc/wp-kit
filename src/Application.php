<?php

declare(strict_types=1);

namespace Period\WpFramework;

use Period\WpFramework\Infrastructure\ShortcodeRegistrar;
use Period\WpFramework\Infrastructure\Shortcode\ButtonShortcode;
use Period\WpFramework\Infrastructure\Shortcode\FetchTitleShortcode;
use Period\WpFramework\Infrastructure\Shortcode\TemplateUrlShortcode;
use Period\WpFramework\Infrastructure\WordPress\NavMenuClassEnhancer;
use Period\WpFramework\Infrastructure\WordPress\PostClassEnhancer;
use Period\WpFramework\Infrastructure\WordPress\PostTypeRegistrar;
use Period\WpFramework\Infrastructure\WordPress\ScriptStyleRegistrar;
use Period\WpFramework\Infrastructure\WordPress\DocumentRenderer;
use Period\WpFramework\Infrastructure\WordPress\SiteInfo;
use Period\WpFramework\Infrastructure\WordPress\TitleResolver;
use Period\WpFramework\Infrastructure\WordPress\Translator;
use Period\WpFramework\Support\ArgsResolver;
use Period\WpFramework\View\Renderer;

final class Application
{
    private string $basePath;
    private ArgsResolver $argsResolver;
    private Renderer $renderer;
    private ScriptStyleRegistrar $assets;
    private PostTypeRegistrar $posts;
    private ?Translator $translator = null;
    private ?SiteInfo $siteInfo = null;
    private ?TitleResolver $titleResolver = null;
    private ?DocumentRenderer $documentRenderer = null;
    private bool $booted = false;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->argsResolver = new ArgsResolver();
        $this->renderer = new Renderer($this->basePath . '/templates');
        $this->assets = new ScriptStyleRegistrar($this->basePath);
        $this->posts = new PostTypeRegistrar();
    }

    public function assets(): ScriptStyleRegistrar
    {
        return $this->assets;
    }

    public function posts(): PostTypeRegistrar
    {
        return $this->posts;
    }

    public function translator(): Translator
    {
        if ($this->translator === null) {
            $this->translator = new Translator();
        }

        return $this->translator;
    }

    public function title(): string
    {
        if ($this->titleResolver === null) {
            $this->titleResolver = new TitleResolver($this->site());
        }

        return $this->titleResolver->siteTitle();
    }

    public function site(): SiteInfo
    {
        if ($this->siteInfo === null) {
            $this->siteInfo = new SiteInfo();
        }

        return $this->siteInfo;
    }

    public function document(string $content = '', array $args = []): string
    {
        if ($this->documentRenderer === null) {
            $this->documentRenderer = new DocumentRenderer();
        }

        return $this->documentRenderer->render($content, $args);
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $shortcodes = [
            new ButtonShortcode($this),
            new FetchTitleShortcode(),
            new TemplateUrlShortcode(),
        ];

        (new ShortcodeRegistrar($shortcodes))->register();
        (new NavMenuClassEnhancer())->register();
        (new PostClassEnhancer())->register();

        $this->booted = true;
    }

    public function button(array $args = []): string
    {
        $args = $this->argsResolver->resolve($args, [
            'label' => 'Button',
            'url' => '',
            'class' => '',
        ]);

        return $this->renderer->render('button', [
            'label' => (string) $args['label'],
            'url' => (string) $args['url'],
            'class' => trim('period-wp-button ' . (string) $args['class']),
        ]);
    }

    public function basePath(): string
    {
        return $this->basePath;
    }
}
