<?php

declare(strict_types=1);

namespace Period\WpKit\View;

final class Renderer
{
    private string $templatePath;

    public function __construct(string $templatePath)
    {
        $this->templatePath = rtrim($templatePath, '/');
    }

    public function render(string $template, array $vars = []): string
    {
        $file = $this->templatePath . '/' . ltrim($template, '/') . '.php';

        if (!is_file($file)) {
            return '';
        }

        ob_start();

        extract($vars, EXTR_SKIP);

        require $file;

        return (string) ob_get_clean();
    }
}
