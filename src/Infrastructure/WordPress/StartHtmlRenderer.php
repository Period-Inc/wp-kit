<?php

declare(strict_types=1);

namespace Period\WpFramework\Infrastructure\WordPress;

use Period\WpFramework\View\Element;
use Period\WpFramework\View\RawHtml;

final class StartHtmlRenderer
{
    public function render(array $args = []): string
    {
        $args = $this->normalizeArgs($args);
        $newline = $args['newline'];
        $version = $args['version'];

        $languageAttributes = $this->resolveLanguageAttributes($version);
        $charset = $this->resolveCharset($args['charset']);

        $html = '<!doctype html>' . $newline . $newline;

        if ($languageAttributes !== '') {
            $html .= '<html ' . $languageAttributes . '>' . $newline;
        } else {
            $html .= (new Element('html', ['lang' => 'ja']))->open()->render() . $newline;
        }

        $html .= (new Element('head'))->open()->render() . $newline;
        $html .= Element::el('meta', ['charset' => $charset]) . $newline;
        $html .= Element::el('title', [], $this->resolveTitle()) . $newline;

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

        return $html;
    }

    private function normalizeArgs(array $args): array
    {
        $version = $args['version'] ?? 'html5';
        $elements = $args['elements'] ?? [];
        $charset = $args['charset'] ?? null;
        $newline = $args['newline'] ?? "\n";

        return [
            'version' => is_string($version) && $version !== '' ? $version : 'html5',
            'elements' => is_array($elements) ? $elements : [],
            'charset' => is_string($charset) && $charset !== '' ? $charset : null,
            'newline' => is_string($newline) ? $newline : "\n",
        ];
    }

    private function resolveTitle(): string
    {
        $siteInfo = new SiteInfo();
        $resolver = new TitleResolver($siteInfo);
        $formatter = new TemplateFormatter();

        return $formatter->format(
            '{{ title }}',
            ['title' => $resolver->siteTitle()],
            'period_wp_document_title'
        );
    }

    private function resolveLanguageAttributes(string $version): string
    {
        if (function_exists('language_attributes')) {
            $type = str_starts_with($version, 'xhtml') ? 'xhtml' : 'html';
            return trim(language_attributes($type));
        }

        return 'lang="ja"';
    }

    private function resolveCharset(?string $charset): string
    {
        if ($charset !== null) {
            return $charset;
        }

        if (function_exists('get_bloginfo')) {
            $blogCharset = get_bloginfo('charset');
            if (is_string($blogCharset) && $blogCharset !== '') {
                return $blogCharset;
            }
        }

        return 'UTF-8';
    }
}
