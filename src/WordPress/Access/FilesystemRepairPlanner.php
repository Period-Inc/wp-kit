<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class FilesystemRepairPlanner
{
    public function __construct(
        private readonly FilesystemInspectorInterface $inspector,
        private readonly string $privateAssetRoot,
    ) {}

    public function plan(): AssetAccessRepairPlan
    {
        if (!$this->inspector->exists($this->privateAssetRoot)) {
            return new AssetAccessRepairPlan([
                AssetAccessRepairAction::createDirectory($this->privateAssetRoot),
            ]);
        }

        $actions = [];

        if (!$this->inspector->isWritable($this->privateAssetRoot)) {
            $actions[] = AssetAccessRepairAction::permissionWarning($this->privateAssetRoot);
        }

        if (!$this->inspector->isReadable($this->privateAssetRoot)) {
            $actions[] = AssetAccessRepairAction::readabilityWarning($this->privateAssetRoot);
        }

        return new AssetAccessRepairPlan($actions);
    }
}
