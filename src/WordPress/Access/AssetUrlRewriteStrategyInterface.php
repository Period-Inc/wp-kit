<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface AssetUrlRewriteStrategyInterface
{
    public function rewrite(string $originalUrl, string $protectedPath): string;
}
