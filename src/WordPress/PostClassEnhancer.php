<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

use Period\WpFramework\Support\CssName;

final class PostClassEnhancer
{
    public function register(): void
    {
        if (!function_exists('add_filter')) {
            return;
        }

        add_filter('post_class', [$this, 'addClasses'], 10, 3);
    }

    /**
     * @param array $classes
     * @param array|string $cssClass
     * @param int|object|null $postId
     * @return array
     */
    public function addClasses(array $classes, array|string $cssClass = '', int|object|null $postId = null): array
    {
        $classes[] = 'article';

        if (function_exists('is_singular') && is_singular()) {
            $classes[] = 'singular';
        } else {
            $classes[] = 'posts';
        }

        $post = $this->resolvePost($postId);

        if ($post !== null) {
            if (isset($post->post_type) && is_string($post->post_type) && $post->post_type !== '') {
                $classes[] = 'post_type__' . CssName::fromString($post->post_type);
            }

            if (isset($post->post_name) && is_string($post->post_name) && $post->post_name !== '') {
                $classes[] = 'post_name__' . CssName::fromString($post->post_name);
            }

            if (function_exists('current_theme_supports') && function_exists('has_post_thumbnail')) {
                if (current_theme_supports('post-thumbnails') && !has_post_thumbnail($post)) {
                    $classes[] = 'no-post-thumbnail';
                }
            }
        }

        $postCount = $this->resolvePostCount();
        if ($postCount !== null) {
            $classes[] = 'post_count__' . $postCount;
            $classes[] = $postCount % 2 === 0 ? 'post_count__even' : 'post_count__odd';
        }

        return array_values(array_unique($classes));
    }

    private function resolvePost(int|object|null $postId): ?object
    {
        if (is_object($postId)) {
            return $postId;
        }

        if ($postId !== null && function_exists('get_post')) {
            return get_post($postId);
        }

        if ($postId === null && function_exists('get_post')) {
            return get_post();
        }

        return null;
    }

    private function resolvePostCount(): ?int
    {
        global $wp_query;

        if (!isset($wp_query) || !is_object($wp_query)) {
            return null;
        }

        if (!property_exists($wp_query, 'current_post')) {
            return null;
        }

        $currentPost = $wp_query->current_post;
        if (!is_int($currentPost)) {
            return null;
        }

        return $currentPost + 1;
    }
}
