<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAttachmentUrlFilter
{
    /** @var callable(int, string, bool): mixed */
    private readonly mixed $getMeta;

    /**
     * @param callable(int, string, bool): mixed $getMeta  Injected get_post_meta equivalent.
     */
    public function __construct(
        callable $getMeta,
        private readonly AssetUrlRewriteStrategyInterface $strategy,
    ) {
        $this->getMeta = $getMeta;
    }

    public function filter(string $url, int $attachmentId): string
    {
        $protected = ($this->getMeta)($attachmentId, '_period_asset_protected', true);

        if (!$protected) {
            return $url;
        }

        $protectedPath = (string) ($this->getMeta)($attachmentId, '_period_asset_protected_path', true);

        if ($protectedPath === '') {
            return $url;
        }

        return $this->strategy->rewrite($url, $protectedPath);
    }
}
