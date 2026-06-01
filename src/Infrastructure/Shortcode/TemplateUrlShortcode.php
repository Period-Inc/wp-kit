<?php

declare(strict_types=1);

namespace Period\WpKit\Infrastructure\Shortcode;

final class TemplateUrlShortcode implements ShortcodeInterface
{
    public function register(): void
    {
        if (!function_exists('add_shortcode')) {
            return;
        }

        add_shortcode('period_wp_template_url', [$this, 'renderTemplateUrl']);
        add_shortcode('period_wp_stylesheet_directory_url', [$this, 'renderStylesheetDirectoryUrl']);
        add_shortcode('period_wp_home_url', [$this, 'renderHomeUrl']);
    }

    public function renderTemplateUrl(array|string $atts = []): string
    {
        if (!function_exists('get_template_directory_uri')) {
            return '';
        }

        return get_template_directory_uri() . $this->resolveSuffix($atts);
    }

    public function renderStylesheetDirectoryUrl(array|string $atts = []): string
    {
        if (!function_exists('get_stylesheet_directory_uri')) {
            return '';
        }

        return get_stylesheet_directory_uri() . $this->resolveSuffix($atts);
    }

    public function renderHomeUrl(array|string $atts = []): string
    {
        if (!function_exists('home_url')) {
            return '';
        }

        return home_url() . $this->resolveSuffix($atts);
    }

    private function resolveSuffix(array|string $atts = []): string
    {
        if (!is_array($atts)) {
            return '';
        }

        return isset($atts['suffix']) ? (string) $atts['suffix'] : '';
    }
}
