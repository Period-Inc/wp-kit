<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action): bool
    {
        return true;
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($postId, $metaKey, $metaValue): bool
    {
        global $METABOX_TEST_META_UPDATES;

        if (!is_array($METABOX_TEST_META_UPDATES)) {
            $METABOX_TEST_META_UPDATES = [];
        }

        $METABOX_TEST_META_UPDATES[] = [
            'post_id' => $postId,
            'key' => $metaKey,
            'value' => $metaValue,
        ];

        return true;
    }
}

if (!function_exists('wp_enqueue_media')) {
    function wp_enqueue_media(array $args = []): void {}
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hook_name, mixed $value, mixed ...$args): mixed
    {
        global $PERIOD_WP_FILTER_VALUES;

        return array_key_exists($hook_name, (array) $PERIOD_WP_FILTER_VALUES)
            ? $PERIOD_WP_FILTER_VALUES[$hook_name]
            : $value;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], mixed $ver = false, bool $in_footer = false): void
    {
        global $PERIOD_WP_ENQUEUED_SCRIPTS;

        if (!is_array($PERIOD_WP_ENQUEUED_SCRIPTS)) {
            $PERIOD_WP_ENQUEUED_SCRIPTS = [];
        }

        $PERIOD_WP_ENQUEUED_SCRIPTS[] = compact('handle', 'src', 'deps', 'ver', 'in_footer');
    }
}
