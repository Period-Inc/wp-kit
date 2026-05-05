<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\WordPress;

final class TemplateFormatter
{
    public function format(string $template, array $context = [], string $filter = ''): string
    {
        $result = preg_replace_callback(
            '/\{\{\s*([\w.]+)\s*\}\}/',
            function (array $matches) use ($context): string {
                $key = $matches[1];
                if (!array_key_exists($key, $context)) {
                    return '';
                }
                $value = $context[$key];
                if ($value === null || is_array($value) || is_object($value)) {
                    return '';
                }
                return (string) $value;
            },
            $template
        );

        $result = trim($result ?? '');

        if ($filter !== '' && function_exists('apply_filters')) {
            $result = (string) apply_filters($filter, $result, $template, $context);
        }

        return $result;
    }
}
