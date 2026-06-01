<?php

declare(strict_types=1);

namespace Period\WpKit\Infrastructure;

use Period\WpKit\Infrastructure\Shortcode\ShortcodeInterface;

final class ShortcodeRegistrar
{
    /**
     * @param ShortcodeInterface[] $shortcodes
     */
    public function __construct(private array $shortcodes)
    {
    }

    public function register(): void
    {
        foreach ($this->shortcodes as $shortcode) {
            if ($shortcode instanceof ShortcodeInterface) {
                $shortcode->register();
            }
        }
    }
}
