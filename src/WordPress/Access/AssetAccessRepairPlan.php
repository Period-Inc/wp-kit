<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAccessRepairPlan
{
    /** @param AssetAccessRepairAction[] $actions */
    public function __construct(private readonly array $actions) {}

    /** @return AssetAccessRepairAction[] */
    public function actions(): array
    {
        return $this->actions;
    }

    public function hasActions(): bool
    {
        return $this->actions !== [];
    }
}
