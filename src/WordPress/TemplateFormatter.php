<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

use Period\WpFramework\Support\TemplateFormatter as BaseTemplateFormatter;

/**
 * @deprecated Use Period\WpFramework\Support\TemplateFormatter instead.
 *             apply_filters support has been moved to the call site.
 */
final class TemplateFormatter extends BaseTemplateFormatter
{
    public function format(string $template, array $context = [], string $filter = ''): string
    {
        $result = parent::format($template, $context);

        if ($filter !== '' && function_exists('apply_filters')) {
            $result = (string) apply_filters($filter, $result, $template, $context);
        }

        return $result;
    }
}
