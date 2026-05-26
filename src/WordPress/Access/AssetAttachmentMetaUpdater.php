<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAttachmentMetaUpdater
{
    /** @var callable(int, string, mixed): void */
    private readonly mixed $updateMeta;

    /**
     * @param callable(int, string, mixed): void $updateMeta
     */
    public function __construct(callable $updateMeta)
    {
        $this->updateMeta = $updateMeta;
    }

    /**
     * @param array<string,mixed> $upload
     */
    public function update(int $attachmentId, array $upload): void
    {
        $moveResult = $upload['asset_move_result'] ?? null;

        if (!($moveResult instanceof AssetFileMoveResult) || !$moveResult->isSuccess()) {
            return;
        }

        ($this->updateMeta)($attachmentId, '_period_asset_protected', '1');
        ($this->updateMeta)($attachmentId, '_period_asset_protected_path', (string) ($upload['file'] ?? ''));

        if (array_key_exists('url', $upload)) {
            ($this->updateMeta)($attachmentId, '_period_asset_delivery_url', (string) $upload['url']);
        }
    }
}
