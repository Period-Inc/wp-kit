<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessRepairRequest
{
    public function __construct(
        private readonly bool $confirmed,
        private readonly string $nonce,
        private readonly bool $currentUserCanManage,
    ) {}

    public function confirmed(): bool
    {
        return $this->confirmed;
    }

    public function nonce(): string
    {
        return $this->nonce;
    }

    public function currentUserCanManage(): bool
    {
        return $this->currentUserCanManage;
    }
}
