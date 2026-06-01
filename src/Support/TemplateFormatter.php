<?php

declare(strict_types=1);

namespace Period\WpKit\Support;

class TemplateFormatter
{
    public function format(string $template, array $context = []): string
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

        return trim($result ?? '');
    }
}
