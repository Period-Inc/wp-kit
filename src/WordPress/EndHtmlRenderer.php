<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

use Period\WpFramework\View\Element;
use Period\WpFramework\View\RawHtml;

final class EndHtmlRenderer
{
    public function render(array $args = []): string
    {
        $args = $this->normalizeArgs($args);
        $newline = $args['newline'];

        $html = '';
        foreach ($args['elements'] as $element) {
            if (is_string($element)) {
                $html .= $element . $newline;
                continue;
            }

            if ($element instanceof RawHtml) {
                $html .= $element->render() . $newline;
                continue;
            }

            if ($element instanceof Element) {
                $html .= $element->render() . $newline;
                continue;
            }
        }

        if ($args['include_wp_footer'] && function_exists('wp_footer')) {
            ob_start();
            wp_footer();
            $footerContent = ob_get_clean();
            if ($footerContent !== false && $footerContent !== '') {
                $html .= $footerContent;
                if (!str_ends_with($footerContent, $newline)) {
                    $html .= $newline;
                }
            }
        }

        $html .= '</body>' . $newline;
        $html .= '</html>';

        return $html;
    }

    private function normalizeArgs(array $args): array
    {
        $elements = $args['elements'] ?? [];
        $newline = $args['newline'] ?? "\n";
        $includeWpFooter = isset($args['include_wp_footer']) ? (bool) $args['include_wp_footer'] : true;

        return [
            'elements' => is_array($elements) ? $elements : [],
            'newline' => is_string($newline) ? $newline : "\n",
            'include_wp_footer' => $includeWpFooter,
        ];
    }
}
