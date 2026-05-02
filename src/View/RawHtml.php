<?php

declare(strict_types=1);

namespace Period\WpFramework\View;

final class RawHtml
{
    public function __construct(
        private string $html
    ) {
    }

    public function render(): string
    {
        return $this->html;
    }
}
