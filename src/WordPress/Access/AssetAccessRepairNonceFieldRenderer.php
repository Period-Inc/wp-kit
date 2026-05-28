<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAccessRepairNonceFieldRenderer
{
    /** @var callable(string): mixed */
    private readonly mixed $nonceField;

    /** @param callable(string): mixed $nonceField */
    public function __construct(callable $nonceField)
    {
        $this->nonceField = $nonceField;
    }

    public function render(): string
    {
        return (string) ($this->nonceField)('period_asset_access_repair');
    }
}
