<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

final class ScriptStyleRegistrar
{
    private string $basePath;
    private array $registeredScripts = [];
    private array $registeredStyles = [];

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * @param array{deps?: array, ver?: string|int|null, in_footer?: bool, path?: string|null, enqueue?: bool} $args
     */
    public function script(string $handle, string $src, array $args = []): self
    {
        $deps = $args['deps'] ?? [];
        $ver = $args['ver'] ?? null;
        $inFooter = $args['in_footer'] ?? true;
        $path = $args['path'] ?? null;
        $enqueue = $args['enqueue'] ?? false;

        if (function_exists('wp_register_script')) {
            $resolvedVersion = $this->resolveVersion($path, $ver);
            wp_register_script($handle, $src, $deps, $resolvedVersion, $inFooter);

            if ($enqueue && function_exists('wp_enqueue_script')) {
                wp_enqueue_script($handle);
            }
        }

        $this->registeredScripts[$handle] = true;

        return $this;
    }

    /**
     * @param array{deps?: array, ver?: string|int|null, media?: string, path?: string|null, enqueue?: bool} $args
     */
    public function style(string $handle, string|false $src, array $args = []): self
    {
        $deps = $args['deps'] ?? [];
        $ver = $args['ver'] ?? null;
        $media = $args['media'] ?? 'all';
        $path = $args['path'] ?? null;
        $enqueue = $args['enqueue'] ?? false;

        if (function_exists('wp_register_style')) {
            $resolvedVersion = $this->resolveVersion($path, $ver);
            wp_register_style($handle, $src, $deps, $resolvedVersion, $media);

            if ($enqueue && function_exists('wp_enqueue_style')) {
                wp_enqueue_style($handle);
            }
        }

        $this->registeredStyles[$handle] = true;

        return $this;
    }

    public function enqueue(string $handle): void
    {
        if (isset($this->registeredScripts[$handle]) && function_exists('wp_enqueue_script')) {
            wp_enqueue_script($handle);
            return;
        }

        if (isset($this->registeredStyles[$handle]) && function_exists('wp_enqueue_style')) {
            wp_enqueue_style($handle);
        }
    }

    public function inlineScript(string $handle, string $code): void
    {
        if (function_exists('wp_add_inline_script')) {
            wp_add_inline_script($handle, $code);
        }
    }

    public function inlineStyle(string $handle, string $code): void
    {
        if (function_exists('wp_add_inline_style')) {
            wp_add_inline_style($handle, $code);
        }
    }

    /**
     * @param string|int|null $ver
     */
    private function resolveVersion(?string $path, string|int|null $ver): string|int|null
    {
        if ($ver !== null) {
            return $ver;
        }

        if ($path === null || $path === '') {
            return null;
        }

        if (file_exists($path)) {
            return filemtime($path);
        }

        return null;
    }
}
