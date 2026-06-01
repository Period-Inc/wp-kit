<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetBulkProtectionProcessor
{
    /** @var callable(int, string, mixed): void */
    private readonly mixed $updateMeta;

    /** @param callable(int, string, mixed): void $updateMeta */
    public function __construct(callable $updateMeta)
    {
        $this->updateMeta = $updateMeta;
    }

    /** @param array<mixed> $attachmentIds */
    public function process(array $attachmentIds, bool $protected): AssetBulkProtectionResult
    {
        $validIds = $this->filterValidIds($attachmentIds);
        $value    = $protected ? '1' : '0';

        foreach ($validIds as $id) {
            ($this->updateMeta)($id, '_period_asset_protected', $value);
        }

        return $protected
            ? AssetBulkProtectionResult::protect($validIds)
            : AssetBulkProtectionResult::unprotect($validIds);
    }

    /** @param array<mixed> $ids @return array<int> */
    private function filterValidIds(array $ids): array
    {
        $valid = [];
        foreach ($ids as $id) {
            $int = (int) $id;
            if ($int > 0) {
                $valid[] = $int;
            }
        }
        return $valid;
    }
}
