<?php

declare(strict_types=1);

namespace Period\WpFramework;

use Period\WpFramework\Infrastructure\ShortcodeRegistrar;
use Period\WpFramework\Infrastructure\Shortcode\ButtonShortcode;
use Period\WpFramework\Infrastructure\Shortcode\FetchTitleShortcode;
use Period\WpFramework\Infrastructure\Shortcode\TemplateUrlShortcode;
use Period\WpFramework\Infrastructure\WordPress\NavMenuClassEnhancer;
use Period\WpFramework\Infrastructure\WordPress\PostTypeRegistrar;
use Period\WpFramework\Infrastructure\WordPress\ScriptStyleRegistrar;
use Period\WpFramework\Support\ArgsResolver;
use Period\WpFramework\View\Renderer;

final class Application
{
    private string $basePath;
    private ArgsResolver $argsResolver;
    private Renderer $renderer;
    private ScriptStyleRegistrar $assets;
    private PostTypeRegistrar $posts;
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
