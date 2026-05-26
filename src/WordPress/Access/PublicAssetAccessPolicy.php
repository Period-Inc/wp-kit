<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class PublicAssetAccessPolicy implements AssetAccessPolicyInterface
{
    public function evaluate(AssetRequestContext $context): AssetAccessResult
    {
        return AssetAccessResult::allow();
    }
}
