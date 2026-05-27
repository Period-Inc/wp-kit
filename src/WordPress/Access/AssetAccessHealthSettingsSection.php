<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAccessHealthSettingsSection
{
    public function __construct(
        private readonly AssetAccessHealthReporter $reporter,
        private readonly AssetAccessHealthStatusRenderer $renderer,
    ) {}

    public function render(): string
    {
        return $this->renderer->render($this->reporter->report());
    }
}
