<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface AssetAccessPolicyInterface
{
    public function evaluate(AssetRequestContext $context): AssetAccessResult;
}
