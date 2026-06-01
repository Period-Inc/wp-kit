<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class OutsideWebrootHealthCheck implements AssetAccessHealthCheckInterface
{
    public function __construct(private readonly ProtectedAssetPathStrategyInterface $strategy) {}

    public function check(): array
    {
        if ($this->strategy instanceof OutsideWebrootAssetPathStrategy) {
            return [
                AssetAccessHealthStatus::info(
                    'outside_webroot_active',
                    'outside webroot strategy active',
                ),
            ];
        }

        return [
            AssetAccessHealthStatus::warning(
                'outside_webroot_not_enabled',
                'outside webroot not enabled',
            ),
        ];
    }
}
