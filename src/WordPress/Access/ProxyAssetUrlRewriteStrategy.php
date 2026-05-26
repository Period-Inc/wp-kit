<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class ProxyAssetUrlRewriteStrategy implements AssetUrlRewriteStrategyInterface
{
    public function __construct(
        private readonly string $baseDeliveryUrl,
    ) {}

    public function rewrite(string $originalUrl, string $protectedPath): string
    {
        return $this->baseDeliveryUrl . '?asset=' . rawurlencode(ltrim($protectedPath, '/'));
    }
}
