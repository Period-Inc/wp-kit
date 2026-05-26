<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface AssetAccessHealthCheckInterface
{
    /** @return AssetAccessHealthStatus[] */
    public function check(): array;
}
