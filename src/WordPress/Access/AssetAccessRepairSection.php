<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessRepairSection
{
    public function __construct(
        private readonly FilesystemRepairPlanner $planner,
        private readonly AssetAccessRepairPlanRenderer $renderer,
    ) {}

    public function render(): string
    {
        return $this->renderer->render($this->planner->plan());
    }
}
