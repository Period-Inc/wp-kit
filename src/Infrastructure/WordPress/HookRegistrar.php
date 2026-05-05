<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\WordPress;

final class HookRegistrar
{
    public function action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): self
    {
        if (function_exists('add_action')) {
            add_action($hook, $callback, $priority, $acceptedArgs);
        }

        return $this;
    }

    public function filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): self
    {
        if (function_exists('add_filter')) {
            add_filter($hook, $callback, $priority, $acceptedArgs);
        }

        return $this;
    }

    public function shortcode(string $tag, callable $callback): self
    {
        if (function_exists('add_shortcode')) {
            add_shortcode($tag, $callback);
        }

        return $this;
    }
}
