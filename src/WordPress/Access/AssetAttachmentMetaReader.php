<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAttachmentMetaReader
{
    /** @var callable(int, string, bool): mixed */
    private readonly mixed $getMeta;

    /**
     * @param callable(int, string, bool): mixed $getMeta  Injected get_post_meta equivalent.
     */
    public function __construct(callable $getMeta)
    {
        $this->getMeta = $getMeta;
    }

    public function read(int $attachmentId): AssetAttachmentMeta
    {
        $protected    = (bool) ($this->getMeta)($attachmentId, '_period_asset_protected',      true);
        $protectedPath = $this->normalizeString(($this->getMeta)($attachmentId, '_period_asset_protected_path', true));
        $deliveryUrl   = $this->normalizeString(($this->getMeta)($attachmentId, '_period_asset_delivery_url',   true));

        return new AssetAttachmentMeta($protected, $protectedPath, $deliveryUrl);
    }

    private function normalizeString(mixed $raw): ?string
    {
        if ($raw === '' || $raw === false || $raw === null) {
            return null;
        }

        return (string) $raw;
    }
}
