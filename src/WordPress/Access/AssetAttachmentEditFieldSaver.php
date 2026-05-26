<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAttachmentEditFieldSaver
{
    /** @var callable(int, string, mixed): void */
    private readonly mixed $updateMeta;

    /** @param callable(int, string, mixed): void $updateMeta */
    public function __construct(callable $updateMeta)
    {
        $this->updateMeta = $updateMeta;
    }

    /** @param array<string,mixed> $data */
    public function save(int $attachmentId, array $data): void
    {
        $protected = isset($data['period_asset_access_protected'])
            && $data['period_asset_access_protected'] === '1';

        ($this->updateMeta)($attachmentId, '_period_asset_protected', $protected ? '1' : '0');
    }
}
