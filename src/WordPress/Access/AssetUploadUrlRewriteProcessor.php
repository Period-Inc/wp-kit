<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetUploadUrlRewriteProcessor
{
    public function __construct(
        private readonly AssetUrlRewriteStrategyInterface $strategy,
    ) {}

    /**
     * @param array<string,mixed> $upload  Upload array with 'file' already set to protected path.
     * @return array<string,mixed>
     */
    public function process(array $upload): array
    {
        if (!array_key_exists('url', $upload)) {
            return $upload;
        }

        $protectedPath  = (string) ($upload['file'] ?? '');
        $originalUrl    = (string) $upload['url'];

        $upload['url']               = $this->strategy->rewrite($originalUrl, $protectedPath);
        $upload['asset_url_rewritten'] = true;

        return $upload;
    }
}
