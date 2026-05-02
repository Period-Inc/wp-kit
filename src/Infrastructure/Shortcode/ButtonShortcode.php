<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\Shortcode;

use Period\WpFramework\Application;

final class ButtonShortcode implements ShortcodeInterface
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(): void
    {
        if (!function_exists('add_shortcode')) {
            return;
        }

        add_shortcode('period_button', [$this, 'render']);
    }

    public function render(array|string $atts = []): string
    {
        if (!is_array($atts)) {
            $atts = [];
        }

        return $this->app->button($atts);
    }
}
