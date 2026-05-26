<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface AssetAccessPolicyInterface
{
    public function evaluate(AssetRequestContext $context): AssetAccessResult;
}
