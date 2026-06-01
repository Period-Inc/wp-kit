<?php

declare(strict_types=1);

namespace Period\WpKit\Infrastructure\Shortcode;

use Period\WpKit\Infrastructure\Shortcode\ShortcodeInterface;
use Period\WpKit\Support\HtmlDocument;
use Period\WpKit\Support\HtmlTemplate;

final class FetchTitleShortcode implements ShortcodeInterface
{
    public function register(): void
    {
        if (!function_exists('add_shortcode')) {
            return;
        }

        add_shortcode('fetch_title', [$this, 'render']);
    }

    public function render(array|string $atts = []): string
    {
        if (!is_array($atts)) {
            $atts = [];
        }

        $url = trim((string) ($atts['url'] ?? ''));

        if ($url === '') {
            return '';
        }

        $document = HtmlDocument::fromUrl($url);
        $title = $document->firstText('title');

        if ($title === '') {
            return '';
        }

        $template = new HtmlTemplate('<h3 class="fetched-title">{{ title }}</h3>');

        return $template->render(['title' => $title]);
    }
}
