<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface AssetFileMoverInterface
{
    public function move(string $from, string $to): AssetFileMoveResult;
}
