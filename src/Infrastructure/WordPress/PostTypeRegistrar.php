<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\WordPress;

final class PostTypeRegistrar
{
    private array $postTypes = [];
    private array $taxonomies = [];

    public function __construct()
    {
    }

    public function register(string $postType, array $args = []): self
    {
        $this->postTypes[$postType] = $this->resolvePostTypeArgs($args);

        return $this;
    }

    /**
     * @param string|string[] $postTypes
     */
    public function registerTaxonomy(string $taxonomy, string|array $postTypes, array $args = []): self
    {
        $this->taxonomies[] = [
            'taxonomy' => $taxonomy,
            'post_types' => is_string($postTypes) ? [$postTypes] : array_values(array_filter($postTypes, fn ($item) => is_string($item) && $item !== '')),
            'args' => $args,
        ];

        return $this;
    }

    public function boot(): void
    {
        if (!function_exists('add_action')) {
            return;
        }

        $self = $this;

        add_action('init', function () use ($self): void {
            $self->registerAll();
            $self->registerTaxonomiesAll();
        });
    }

    private function registerAll(): void
    {
        if (!function_exists('register_post_type')) {
            return;
        }

        foreach ($this->postTypes as $postType => $args) {
            register_post_type($postType, $args);
        }
    }

    private function registerTaxonomiesAll(): void
    {
        if (!function_exists('register_taxonomy')) {
            return;
        }

        foreach ($this->taxonomies as $taxonomy) {
            register_taxonomy($taxonomy['taxonomy'], $taxonomy['post_types'], $taxonomy['args']);
        }
    }

    private function resolvePostTypeArgs(array $args): array
    {
        $defaults = [
            'public' => true,
            'show_in_rest' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor'],
        ];

        return array_merge($defaults, $args);
    }
}
