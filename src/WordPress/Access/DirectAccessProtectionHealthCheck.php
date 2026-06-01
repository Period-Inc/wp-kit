<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class DirectAccessProtectionHealthCheck implements AssetAccessHealthCheckInterface
{
    public function __construct(private readonly DirectAccessProtectionStrategy $strategy) {}

    public function check(): array
    {
        if ($this->strategy->isRewrite()) {
            return [
                AssetAccessHealthStatus::warning(
                    'direct_access_rewrite_only',
                    'protected assets rely on rewrite interception',
                ),
            ];
        }

        if ($this->strategy->isDeny()) {
            return [
                AssetAccessHealthStatus::info(
                    'direct_access_deny_enabled',
                    'direct access deny strategy enabled',
                ),
            ];
        }

        return [
            AssetAccessHealthStatus::info(
                'direct_access_outside_webroot_enabled',
                'outside webroot strategy enabled',
            ),
        ];
    }
}
