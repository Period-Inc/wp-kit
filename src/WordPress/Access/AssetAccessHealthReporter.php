<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessHealthReporter
{
    /** @param AssetAccessHealthCheckInterface[] $checks */
    public function __construct(private readonly array $checks) {}

    /** @return AssetAccessHealthStatus[] */
    public function report(): array
    {
        $statuses = [];

        foreach ($this->checks as $check) {
            foreach ($check->check() as $status) {
                $statuses[] = $status;
            }
        }

        return $statuses;
    }
}
