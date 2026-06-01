<?php

declare(strict_types=1);

namespace Period\WpKit\View;

final class Element
{
    private const VOID_TAGS = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'source',
        'track',
        'wbr',
    ];

    private string $tag;
    private array $attrs;
    private array|string|null|RawHtml $children;
    private bool $onlyOpen = false;
    private bool $onlyClose = false;

    public static function el(string $tag, array $attrs = [], string|RawHtml|array $content = ''): string
    {
        if (is_array($content)) {
            $raw = '';
            foreach ($content as $item) {
                if ($item instanceof RawHtml) {
                    $raw .= $item->render();
                } elseif (is_string($item)) {
                    $raw .= $item;
                }
            }
            $content = new RawHtml($raw);
        }

        return (new self($tag, $attrs, $content))->render();
    }

    public static function void(string $tag, array $attrs = []): string
    {
        return (new self($tag, $attrs))->render();
    }

    public static function class(array|string|null ...$classes): string
    {
        $normalized = [];

        foreach ($classes as $class) {
            self::flattenClass($class, $normalized);
        }

        $normalized = array_values(array_unique($normalized, SORT_STRING));

        return implode(' ', $normalized);
    }

    private static function flattenClass(array|string|null $value, array &$result): void
    {
        if ($value === null || $value === false || $value === '') {
            return;
        }

        if (is_string($value)) {
            $result[] = $value;
            return;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                self::flattenClass($item, $result);
            }
        }
    }

    public function __construct(string $tag, array $attrs = [], array|string|null|RawHtml $children = null)
    {
        $this->tag = $tag;
        $this->attrs = $attrs;
        $this->children = $children;
    }

    public static function div(array $attrs = [], array|string|null|RawHtml $children = null): self
    {
        return new self('div', $attrs, $children);
    }

    public static function span(array $attrs = [], array|string|null $children = null): self
    {
        return new self('span', $attrs, $children);
    }

    public static function a(array $attrs = [], array|string|null $children = null): self
    {
        return new self('a', $attrs, $children);
    }

    public static function img(array $attrs = []): self
    {
        return new self('img', $attrs);
    }

    public static function br(array $attrs = []): self
    {
        return new self('br', $attrs);
    }

    // ── Normal element shorthands (return string) ────────────────────────

    public static function abbr(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('abbr', $attrs, $content); }
    public static function address(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('address', $attrs, $content); }
    public static function article(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('article', $attrs, $content); }
    public static function aside(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('aside', $attrs, $content); }
    public static function audio(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('audio', $attrs, $content); }
    public static function b(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('b', $attrs, $content); }
    public static function bdi(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('bdi', $attrs, $content); }
    public static function bdo(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('bdo', $attrs, $content); }
    public static function blockquote(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('blockquote', $attrs, $content); }
    public static function body(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('body', $attrs, $content); }
    public static function button(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('button', $attrs, $content); }
    public static function canvas(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('canvas', $attrs, $content); }
    public static function caption(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('caption', $attrs, $content); }
    public static function cite(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('cite', $attrs, $content); }
    public static function code(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('code', $attrs, $content); }
    public static function colgroup(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('colgroup', $attrs, $content); }
    public static function data(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('data', $attrs, $content); }
    public static function datalist(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('datalist', $attrs, $content); }
    public static function dd(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('dd', $attrs, $content); }
    public static function del(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('del', $attrs, $content); }
    public static function details(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('details', $attrs, $content); }
    public static function dfn(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('dfn', $attrs, $content); }
    public static function dialog(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('dialog', $attrs, $content); }
    public static function dl(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('dl', $attrs, $content); }
    public static function dt(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('dt', $attrs, $content); }
    public static function em(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('em', $attrs, $content); }
    public static function fieldset(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('fieldset', $attrs, $content); }
    public static function figcaption(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('figcaption', $attrs, $content); }
    public static function figure(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('figure', $attrs, $content); }
    public static function footer(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('footer', $attrs, $content); }
    public static function form(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('form', $attrs, $content); }
    public static function h1(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('h1', $attrs, $content); }
    public static function h2(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('h2', $attrs, $content); }
    public static function h3(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('h3', $attrs, $content); }
    public static function h4(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('h4', $attrs, $content); }
    public static function h5(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('h5', $attrs, $content); }
    public static function h6(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('h6', $attrs, $content); }
    public static function head(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('head', $attrs, $content); }
    public static function header(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('header', $attrs, $content); }
    public static function hgroup(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('hgroup', $attrs, $content); }
    public static function html(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('html', $attrs, $content); }
    public static function i(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('i', $attrs, $content); }
    public static function iframe(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('iframe', $attrs, $content); }
    public static function ins(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('ins', $attrs, $content); }
    public static function kbd(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('kbd', $attrs, $content); }
    public static function label(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('label', $attrs, $content); }
    public static function legend(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('legend', $attrs, $content); }
    public static function li(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('li', $attrs, $content); }
    public static function main(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('main', $attrs, $content); }
    public static function map(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('map', $attrs, $content); }
    public static function mark(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('mark', $attrs, $content); }
    public static function menu(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('menu', $attrs, $content); }
    public static function meter(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('meter', $attrs, $content); }
    public static function nav(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('nav', $attrs, $content); }
    public static function noscript(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('noscript', $attrs, $content); }
    /** @see objectTag() for <object> — 'object' is a PHP reserved word */
    public static function objectTag(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('object', $attrs, $content); }
    public static function ol(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('ol', $attrs, $content); }
    public static function optgroup(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('optgroup', $attrs, $content); }
    public static function option(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('option', $attrs, $content); }
    public static function output(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('output', $attrs, $content); }
    public static function p(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('p', $attrs, $content); }
    public static function picture(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('picture', $attrs, $content); }
    public static function pre(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('pre', $attrs, $content); }
    public static function progress(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('progress', $attrs, $content); }
    public static function q(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('q', $attrs, $content); }
    public static function rp(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('rp', $attrs, $content); }
    public static function rt(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('rt', $attrs, $content); }
    public static function ruby(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('ruby', $attrs, $content); }
    public static function s(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('s', $attrs, $content); }
    public static function samp(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('samp', $attrs, $content); }
    public static function script(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('script', $attrs, $content); }
    public static function search(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('search', $attrs, $content); }
    public static function section(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('section', $attrs, $content); }
    public static function select(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('select', $attrs, $content); }
    public static function slot(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('slot', $attrs, $content); }
    public static function small(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('small', $attrs, $content); }
    public static function strong(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('strong', $attrs, $content); }
    public static function style(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('style', $attrs, $content); }
    public static function sub(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('sub', $attrs, $content); }
    public static function summary(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('summary', $attrs, $content); }
    public static function sup(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('sup', $attrs, $content); }
    public static function table(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('table', $attrs, $content); }
    public static function tbody(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('tbody', $attrs, $content); }
    public static function td(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('td', $attrs, $content); }
    public static function template(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('template', $attrs, $content); }
    public static function textarea(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('textarea', $attrs, $content); }
    public static function tfoot(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('tfoot', $attrs, $content); }
    public static function th(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('th', $attrs, $content); }
    public static function thead(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('thead', $attrs, $content); }
    public static function time(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('time', $attrs, $content); }
    public static function title(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('title', $attrs, $content); }
    public static function tr(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('tr', $attrs, $content); }
    public static function u(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('u', $attrs, $content); }
    public static function ul(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('ul', $attrs, $content); }
    /** @see varTag() for <var> — 'var' is a PHP reserved word */
    public static function varTag(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('var', $attrs, $content); }
    public static function video(array $attrs = [], string|RawHtml|array $content = ''): string { return self::el('video', $attrs, $content); }

    // ── Void element shorthands (return string) ───────────────────────────

    public static function area(array $attrs = []): string { return self::void('area', $attrs); }
    public static function base(array $attrs = []): string { return self::void('base', $attrs); }
    public static function col(array $attrs = []): string { return self::void('col', $attrs); }
    public static function embed(array $attrs = []): string { return self::void('embed', $attrs); }
    public static function hr(array $attrs = []): string { return self::void('hr', $attrs); }
    public static function input(array $attrs = []): string { return self::void('input', $attrs); }
    public static function link(array $attrs = []): string { return self::void('link', $attrs); }
    public static function meta(array $attrs = []): string { return self::void('meta', $attrs); }
    public static function param(array $attrs = []): string { return self::void('param', $attrs); }
    public static function source(array $attrs = []): string { return self::void('source', $attrs); }
    public static function track(array $attrs = []): string { return self::void('track', $attrs); }
    public static function wbr(array $attrs = []): string { return self::void('wbr', $attrs); }

    // ─────────────────────────────────────────────────────────────────────

    public static function raw(string $html): RawHtml
    {
        return new RawHtml($html);
    }

    public static function comment(string $text): RawHtml
    {
        return new RawHtml('<!-- ' . $text . ' -->');
    }

    public static function cdata(string $text): RawHtml
    {
        return new RawHtml('<![CDATA[' . $text . ']]>');
    }

    public static function elIfNotEmpty(string $tag, array $attrs, string $content): string
    {
        if (trim($content) === '') {
            return '';
        }

        return self::el($tag, $attrs, $content);
    }

    public function attr(string $key, $value): self
    {
        $this->attrs[$key] = $value;

        return $this;
    }

    public function open(): self
    {
        $this->onlyOpen = true;
        $this->onlyClose = false;
        return $this;
    }

    public function close(): self
    {
        $this->onlyClose = true;
        $this->onlyOpen = false;
        return $this;
    }

    public function render(): string
    {
        $attributes = $this->renderAttributes();

        if ($this->onlyOpen) {
            return sprintf('<%s%s>', $this->tag, $attributes);
        }

        if ($this->onlyClose) {
            return sprintf('</%s>', $this->tag);
        }

        if ($this->isVoidTag()) {
            return sprintf('<%s%s>', $this->tag, $attributes);
        }

        $content = $this->renderChildren();

        return sprintf('<%s%s>%s</%s>', $this->tag, $attributes, $content, $this->tag);
    }

    private function renderAttributes(): string
    {
        $result = '';

        foreach ($this->attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === '' && $key !== 'alt') {
                continue;
            }

            // 属性名の簡易バリデーション
            if (!preg_match('/^[a-zA-Z_:][-a-zA-Z0-9_:.]*$/', (string) $key)) {
                continue;
            }

            if ($key === 'class' && is_array($value)) {
                $value = self::class($value);
                if ($value === '') {
                    continue;
                }
            }

            if ($value === true) {
                $escapedKey = $this->escapeHtml((string) $key);
                $result .= ' ' . $escapedKey;
                continue;
            }

            if ((is_array($value) || is_object($value)) && str_starts_with((string) $key, 'data-')) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if ($value === false) {
                    continue;
                }
            }

            if (!is_scalar($value)) {
                continue;
            }

            $escapedKey = $this->escapeHtml((string) $key);
            $escapedValue = $this->escapeAttr((string) $value);

            $result .= ' ' . $escapedKey . '="' . $escapedValue . '"';
        }

        return $result;
    }

    private function renderChildren(): string
    {
        if ($this->children === null) {
            return '';
        }

        if ($this->children instanceof RawHtml) {
            return $this->children->render();
        }

        if (is_string($this->children)) {
            return $this->escapeHtml($this->children);
        }

        $html = '';

        foreach ($this->children as $child) {
            if ($child === null) {
                continue;
            }

            if ($child instanceof self) {
                $html .= $child->render();
                continue;
            }

            if ($child instanceof RawHtml) {
                $html .= $child->render();
                continue;
            }

            if (!is_scalar($child)) {
                continue;
            }

            $html .= $this->escapeHtml((string) $child);
        }

        return $html;
    }

    private function isVoidTag(): bool
    {
        return in_array(strtolower($this->tag), self::VOID_TAGS, true);
    }

    private function escapeAttr(string $value): string
    {
        if (function_exists('esc_attr')) {
            return esc_attr($value);
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function escapeHtml(string $value): string
    {
        if (function_exists('esc_html')) {
            return esc_html($value);
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
