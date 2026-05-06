<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

final class SiteInfo
{
    public function name(): string
    {
        if (function_exists('get_bloginfo')) {
            return (string) get_bloginfo('name');
        }

        return '';
    }

    public function description(): string
    {
        if (function_exists('get_bloginfo')) {
            return (string) get_bloginfo('description');
        }

        return '';
    }

    public function charset(): string
    {
        if (function_exists('get_bloginfo')) {
            $value = (string) get_bloginfo('charset');
            if ($value !== '') {
                return $value;
            }
        }

        return 'UTF-8';
    }

    public function language(): string
    {
        if (function_exists('get_bloginfo')) {
            $value = (string) get_bloginfo('language');
            if ($value !== '') {
                return $value;
            }
        }

        return 'en';
    }

    public function url(): string
    {
        if (function_exists('home_url')) {
            return (string) home_url();
        }

        return '';
    }

    public function themeUri(): string
    {
        if (function_exists('get_stylesheet_directory_uri')) {
            return (string) get_stylesheet_directory_uri();
        }

        return '';
    }
}
