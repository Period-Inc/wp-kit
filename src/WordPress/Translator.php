<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress;

final class Translator
{
    private string $textDomain;

    public function __construct(string $textDomain = 'period-wp-framework')
    {
        $this->textDomain = $textDomain;
    }

    public function text(string $text): string
    {
        if (function_exists('__')) {
            return __($text, $this->textDomain);
        }

        return $text;
    }

    public function html(string $text): string
    {
        if (function_exists('esc_html__')) {
            return esc_html__($text, $this->textDomain);
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function attr(string $text): string
    {
        if (function_exists('esc_attr__')) {
            return esc_attr__($text, $this->textDomain);
        }

        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public function plural(string $single, string $plural, int $number): string
    {
        if (function_exists('_n')) {
            return _n($single, $plural, $number, $this->textDomain);
        }

        return $number === 1 ? $single : $plural;
    }

    public function domain(): string
    {
        return $this->textDomain;
    }
}
