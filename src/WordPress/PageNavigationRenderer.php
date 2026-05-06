<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

use Period\WpFramework\View\Element;
use Period\WpFramework\View\RawHtml;

final class PageNavigationRenderer
{
    public function render(array $args = []): string
    {
        if (!function_exists('paginate_links') || !function_exists('get_pagenum_link')) {
            return '';
        }

        $args = $this->normalizeArgs($args);
        $ariaLabel = $args['aria_label'];

        global $wp_query;

        if (!isset($wp_query) || !is_object($wp_query) || !isset($wp_query->max_num_pages)) {
            return '';
        }

        $maxPages = (int) $wp_query->max_num_pages;
        if ($maxPages <= 1) {
            return '';
        }

        $current = $this->resolveCurrentPage();
        $content = '';

        if ($args['show_numbers']) {
            $content = $this->renderPaginateLinks(
                $current,
                $maxPages,
                $args['prev_label'],
                $args['next_label'],
                $args['class'],
                $ariaLabel
            );

            if (str_starts_with(trim($content), '<nav')) {
                return $content;
            }
        } else {
            $content = $this->renderPrevNextLinks($current, $maxPages, $args['prev_label'], $args['next_label']);
        }

        if ($content === '') {
            return '';
        }

        $class = htmlspecialchars($args['class'], ENT_QUOTES, 'UTF-8');
        $typeAttr = htmlspecialchars($args['type'], ENT_QUOTES, 'UTF-8');
        $ariaLabelAttr = htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<nav class="%s" aria-label="%s" data-type="%s">%s%s%s</nav>',
            $class,
            $ariaLabelAttr,
            $typeAttr,
            $args['before'],
            $content,
            $args['after']
        );
    }

    private function renderPaginateLinks(int $current, int $maxPages, string $prevLabel, string $nextLabel, string $class, string $ariaLabel): string
    {
        $classAttr = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
        $big = 999999999;
        $base = get_pagenum_link($big);
        $base = $this->escapeUrl($base);
        $base = str_replace((string) $big, '%#%', $base);

        $links = paginate_links([
            'base' => $base,
            'format' => '?paged=%#%',
            'current' => $current,
            'total' => $maxPages,
            'prev_text' => $prevLabel,
            'next_text' => $nextLabel,
            'type' => 'plain',
            'mid_size' => 1,
            'end_size' => 1,
            'add_args' => false,
            'add_fragment' => '',
        ]);

        if (is_string($links)) {
            return Element::el(
                'nav',
                [
                    'class' => $class,
                    'aria-label' => $ariaLabel,
                ],
                Element::raw($links)
            );
        }

        if (!is_array($links)) {
            return '';
        }

        $items = '';
        foreach ($links as $link) {
            $isCurrent = is_string($link) && str_contains($link, 'current');
            $items .= Element::el(
                'li',
                ['class' => $isCurrent ? 'is-current' : null],
                Element::raw($link)
            );
        }

        $list = Element::el('ul', [], Element::raw($items));

        return Element::el(
            'nav',
            [
                'class' => $class,
                'aria-label' => $ariaLabel,
            ],
            Element::raw($list)
        );
    }

    private function normalizeArgs(array $args): array
    {
        $type = $args['type'] ?? null;
        $prevLabel = $args['prev_label'] ?? null;
        $nextLabel = $args['next_label'] ?? null;
        $class = $args['class'] ?? null;
        $before = $args['before'] ?? null;
        $after = $args['after'] ?? null;
        $ariaLabel = $args['aria_label'] ?? null;
        $label = $args['label'] ?? null;

        if (is_string($ariaLabel) && $ariaLabel !== '') {
            $resolvedAriaLabel = $ariaLabel;
        } elseif (is_string($label) && $label !== '') {
            $resolvedAriaLabel = $label;
        } else {
            $resolvedAriaLabel = 'pagination';
        }

        return [
            'type' => is_string($type) && $type !== '' ? $type : 'archive',
            'prev_label' => is_string($prevLabel) && $prevLabel !== '' ? $prevLabel : '前へ',
            'next_label' => is_string($nextLabel) && $nextLabel !== '' ? $nextLabel : '次へ',
            'class' => is_string($class) && $class !== '' ? $class : 'period-wp-page-navigation',
            'show_numbers' => isset($args['show_numbers']) ? (bool) $args['show_numbers'] : true,
            'before' => is_string($before) ? $before : '',
            'after' => is_string($after) ? $after : '',
            'aria_label' => $resolvedAriaLabel,
        ];
    }

    private function resolveCurrentPage(): int
    {
        if (function_exists('get_query_var')) {
            $paged = get_query_var('paged');
            if (is_numeric($paged) && (int) $paged > 0) {
                return (int) $paged;
            }
        }

        return 1;
    }

    private function renderPrevNextLinks(int $current, int $maxPages, string $prevLabel, string $nextLabel): string
    {
        $items = [];

        if ($current > 1) {
            $items[] = sprintf(
                '<a href="%s" class="page-nav-prev">%s</a>',
                $this->escapeUrl(get_pagenum_link($current - 1)),
                htmlspecialchars($prevLabel, ENT_QUOTES, 'UTF-8')
            );
        }

        if ($current < $maxPages) {
            $items[] = sprintf(
                '<a href="%s" class="page-nav-next">%s</a>',
                $this->escapeUrl(get_pagenum_link($current + 1)),
                htmlspecialchars($nextLabel, ENT_QUOTES, 'UTF-8')
            );
        }

        return implode('', $items);
    }

    private function escapeUrl(string $url): string
    {
        return function_exists('esc_url') ? esc_url($url) : $url;
    }
}
