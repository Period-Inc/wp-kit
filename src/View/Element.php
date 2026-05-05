<?php

declare(strict_types=1);

namespace Period\WpFramework\View;

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

    public static function el(string $tag, array $attrs = [], string|RawHtml $content = ''): string
    {
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
