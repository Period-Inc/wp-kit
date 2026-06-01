<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

final class PostMetaManager
{
    public function get(int $postId, string $key): mixed
    {
        if (!function_exists('get_post_meta')) {
            return null;
        }

        return get_post_meta($postId, $key, true);
    }

    public function update(int $postId, string $key, mixed $value): bool
    {
        if (!function_exists('update_post_meta')) {
            return false;
        }

        return (bool) update_post_meta($postId, $key, $value);
    }

    public function set(int $postId, string $key, mixed $value): void
    {
        if (!function_exists('update_post_meta')) {
            return;
        }

        update_post_meta($postId, $key, $value);
    }

    public function has(int $postId, string $key): bool
    {
        if (!function_exists('metadata_exists')) {
            return false;
        }

        return (bool) metadata_exists('post', $postId, $key);
    }
}
