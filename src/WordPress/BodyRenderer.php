<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress;

use Period\WpKit\View\Element;

final class BodyRenderer
{
    public function render(array $args = []): string
    {
        $args = $this->normalizeArgs($args);
        $newline = $args['newline'];

        $classes = $this->resolveClasses($args['class']);
        $attrs = $classes !== [] ? ['class' => $classes] : [];

        $html = (new Element('body', $attrs))->open()->render() . $newline;

        if ($args['include_wp_body_open'] && function_exists('wp_body_open')) {
            ob_start();
            wp_body_open();
            $wpBodyOpen = ob_get_clean();
            if ($wpBodyOpen !== false && $wpBodyOpen !== '') {
                $html .= $wpBodyOpen;
                if (!str_ends_with($wpBodyOpen, $newline)) {
                    $html .= $newline;
                }
            }
        }

        return $html;
    }

    private function normalizeArgs(array $args): array
    {
        $class = $args['class'] ?? [];
        $newline = $args['newline'] ?? "\n";
        $includeWpBodyOpen = $args['include_wp_body_open'] ?? true;

        return [
            'class' => $class,
            'newline' => is_string($newline) ? $newline : "\n",
            'include_wp_body_open' => is_bool($includeWpBodyOpen) ? $includeWpBodyOpen : true,
        ];
    }

    private function resolveClasses(mixed $class): array
    {
        $extra = $this->flattenClass($class);

        if (!function_exists('get_body_class')) {
            return $extra;
        }

        $wpClasses = get_body_class($extra);

        return is_array($wpClasses) ? array_values(array_unique($wpClasses, SORT_STRING)) : $extra;
    }

    private function flattenClass(mixed $value): array
    {
        if ($value === null || $value === false || $value === '') {
            return [];
        }

        if (is_string($value) && $value !== '') {
            return array_filter(explode(' ', $value), fn(string $s) => $s !== '');
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $item) {
                foreach ($this->flattenClass($item) as $cls) {
                    $result[] = $cls;
                }
            }
            return $result;
        }

        return [];
    }
}
