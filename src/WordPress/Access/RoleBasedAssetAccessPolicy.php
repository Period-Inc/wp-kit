<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class RoleBasedAssetAccessPolicy implements AssetAccessPolicyInterface
{
    /** @param string[] $allowedRoles */
    public function __construct(
        private readonly array $allowedRoles,
    ) {}

    public function evaluate(AssetRequestContext $context): AssetAccessResult
    {
        if ($this->allowedRoles === []) {
            return AssetAccessResult::deny('No roles configured');
        }

        if (array_intersect($this->allowedRoles, $context->currentUserRoles()) !== []) {
            return AssetAccessResult::allow();
        }

        return AssetAccessResult::deny('User role not permitted');
    }
}
