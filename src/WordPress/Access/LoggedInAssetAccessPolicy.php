<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class LoggedInAssetAccessPolicy implements AssetAccessPolicyInterface
{
    public function evaluate(AssetRequestContext $context): AssetAccessResult
    {
        if ($context->currentUserId() > 0) {
            return AssetAccessResult::allow();
        }

        return AssetAccessResult::deny('Login required');
    }
}
