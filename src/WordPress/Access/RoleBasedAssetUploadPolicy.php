<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class RoleBasedAssetUploadPolicy implements AssetUploadPolicyInterface
{
    /** @param string[] $protectedRoles */
    public function __construct(
        private readonly array $protectedRoles,
    ) {}

    public function decide(AssetRequestContext $context): AssetUploadDecision
    {
        if (
            $this->protectedRoles !== []
            && array_intersect($this->protectedRoles, $context->currentUserRoles()) !== []
        ) {
            return AssetUploadDecision::asProtected($context->assetPath());
        }

        return AssetUploadDecision::asPublic($context->assetPath());
    }
}
