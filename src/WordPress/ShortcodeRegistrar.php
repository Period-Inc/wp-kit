<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

final class ShortcodeRegistrar
{
    public function register(): void
    {
        $hooks = new HookRegistrar();
        $hooks->shortcode('document', [$this, 'renderDocument']);
        $hooks->shortcode('title', [$this, 'renderTitle']);
        $hooks->shortcode('site_name', [$this, 'renderSiteName']);
    }

    public function renderDocument(array|string $atts = [], ?string $content = null): string
    {
        $renderer = new DocumentRenderer();
        return $renderer->render($content ?? '');
    }

    public function renderTitle(array|string $atts = []): string
    {
        $resolver = new TitleResolver(new SiteInfo());
        return $resolver->siteTitle();
    }

    public function renderSiteName(array|string $atts = []): string
    {
        return (new SiteInfo())->name();
    }
}
