<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface AssetAccessHealthCheckInterface
{
    /** @return AssetAccessHealthStatus[] */
    public function check(): array;
}
