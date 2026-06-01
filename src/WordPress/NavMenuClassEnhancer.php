<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

use Period\WpKit\Support\CssName;

final class NavMenuClassEnhancer
{
    public function register(): void
    {
        if (!function_exists('add_filter')) {
            return;
        }

        add_filter('nav_menu_css_class', [$this, 'addClasses'], 10, 4);
    }

    public function addClasses(array $classes, $menuItem, $args, int $depth = 0): array
    {
        $classes = array_values($classes);

        if (isset($menuItem->url) && is_string($menuItem->url) && $menuItem->url !== '') {
            $classes[] = 'menu-item-url-' . CssName::fromUrl($menuItem->url, 'url');
        }

        if (isset($menuItem->object) && is_string($menuItem->object) && $menuItem->object !== '') {
            $classes[] = 'menu-item-object-' . CssName::fromString($menuItem->object);
        }

        if (isset($menuItem->object_id) && function_exists('get_post')) {
            $post = get_post($menuItem->object_id);
            if ($post !== null && isset($post->post_name) && is_string($post->post_name) && $post->post_name !== '') {
                $classes[] = 'menu-item-post-name-' . CssName::fromString($post->post_name, 'post');
            }
        }

        if (isset($args->additional_li_class) && is_string($args->additional_li_class) && $args->additional_li_class !== '') {
            $classes[] = $args->additional_li_class;
        }

        return array_unique($classes);
    }
}
