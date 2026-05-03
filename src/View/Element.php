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
    private array|string|null $children;
    private bool $onlyOpen = false;
    private bool $onlyClose = false;

    public function __construct(string $tag, array $attrs = [], array|string|null $children = null)
    {
        $this->tag = $tag;
        $this->attrs = $attrs;
        $this->children = $children;
    }

    public static function div(array $attrs = [], array|string|null $children = null): self
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

            // 属性名の簡易バリデーション
            if (!preg_match('/^[a-zA-Z_:][-a-zA-Z0-9_:.]*$/', (string) $key)) {
                continue;
            }

            // JSONスイッチ（data-* かつ配列/オブジェクトの場合のみ）
            if ((is_array($value) || is_object($value)) && str_starts_with((string) $key, 'data-')) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if ($value === false) {
                    continue;
                }
            }

            // 非スカラーは無視
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
