<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface AssetAccessSettingsRepositoryInterface
{
    public function get(): AssetAccessSettings;

    public function save(AssetAccessSettings $settings): void;
}
