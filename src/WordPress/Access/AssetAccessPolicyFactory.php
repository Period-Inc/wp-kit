<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessPolicyFactory
{
    public function create(AssetAccessSettings $settings): AssetAccessPolicyInterface
    {
        if (!$settings->isEnabled()) {
            return new PublicAssetAccessPolicy();
        }

        if ($settings->defaultVisibility() !== AssetAccessSettings::VISIBILITY_PRIVATE) {
            return new PublicAssetAccessPolicy();
        }

        $roles = $settings->protectedRoles();

        if ($roles === []) {
            return new PrivateAssetAccessPolicy();
        }

        return new RoleBasedAssetAccessPolicy($roles);
    }
}
