<?php

declare(strict_types=1);

namespace Period\WpFramework\View;

final class Element
{
    private string $tag;
    private array $attrs;
    private array|string|null $children;

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

    public function attr(string $key, $value): self
    {
        $this->attrs[$key] = $value;

        return $this;
    }

    public function render(): string
    {
        $attributes = $this->renderAttributes();
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

            $html .= $this->escapeHtml((string) $child);
        }

        return $html;
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
