<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure;

use Period\WpFramework\Application;
use Period\WpFramework\Infrastructure\Shortcode\FetchTitleShortcode;

final class ShortcodeRegistrar
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register(): void
    {
        add_shortcode('period_button', [$this, 'button']);
        (new FetchTitleShortcode())->register();
    }

    public function button(array|string $atts = []): string
    {
        if (!is_array($atts)) {
            $atts = [];
        }

        return $this->app->button($atts);
    }
}
