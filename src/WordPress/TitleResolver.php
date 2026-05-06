<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

final class TitleResolver
{
    private SiteInfo $siteInfo;

    public function __construct(?SiteInfo $siteInfo = null)
    {
        $this->siteInfo = $siteInfo ?? new SiteInfo();
    }

    public function title(array $args = []): string
    {
        $fallback = isset($args['fallback']) && is_string($args['fallback']) ? $args['fallback'] : null;

        if (function_exists('wp_get_document_title')) {
            $title = (string) wp_get_document_title();
            if ($title !== '') {
                return $title;
            }
        }

        if (function_exists('is_singular') && function_exists('get_the_title') && is_singular()) {
            $title = (string) get_the_title();
            if ($title !== '') {
                return $title;
            }
        }

        if (function_exists('is_archive') && function_exists('get_the_archive_title') && is_archive()) {
            $title = (string) get_the_archive_title();
            if ($title !== '') {
                return $title;
            }
        }

        if (function_exists('is_search') && function_exists('get_search_query') && is_search()) {
            $query = (string) get_search_query();
            if ($query !== '') {
                return 'Search: ' . $query;
            }
        }

        if (function_exists('is_404') && is_404()) {
            return 'Not Found';
        }

        if ($fallback !== null && $fallback !== '') {
            return $fallback;
        }

        return $this->siteInfo->name();
    }

    public function siteTitle(string $separator = ' | '): string
    {
        $title = $this->title();
        $name = $this->siteInfo->name();

        if ($title === '' && $name === '') {
            return '';
        }

        if ($title === '') {
            return $name;
        }

        if ($name === '') {
            return $title;
        }

        if ($title === $name) {
            return $title;
        }

        return $title . $separator . $name;
    }
}
