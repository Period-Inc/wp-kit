<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class NativeAssetFileMover implements AssetFileMoverInterface
{
    public function move(string $from, string $to): AssetFileMoveResult
    {
        if ($to === '') {
            return AssetFileMoveResult::failure($from, $to, 'Destination path is empty');
        }

        if (!file_exists($from)) {
            return AssetFileMoveResult::failure($from, $to, 'Source file does not exist: ' . $from);
        }

        $dir = dirname($to);

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                return AssetFileMoveResult::failure($from, $to, 'Failed to create destination directory: ' . $dir);
            }
        }

        try {
            if (!@rename($from, $to)) {
                return AssetFileMoveResult::failure($from, $to, 'rename() returned false');
            }
        } catch (\Throwable $e) {
            return AssetFileMoveResult::failure($from, $to, $e->getMessage());
        }

        return AssetFileMoveResult::success($from, $to);
    }
}
