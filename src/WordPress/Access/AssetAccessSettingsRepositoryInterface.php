<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface AssetAccessSettingsRepositoryInterface
{
    public function get(): AssetAccessSettings;

    public function save(AssetAccessSettings $settings): void;
}
