<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\Shortcode;

use Period\WpFramework\Infrastructure\Shortcode\ShortcodeInterface;
use Period\WpFramework\Support\HtmlDocument;
use Period\WpFramework\Support\HtmlTemplate;

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
