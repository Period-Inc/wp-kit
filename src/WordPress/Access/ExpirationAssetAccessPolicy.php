<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class ExpirationAssetAccessPolicy implements AssetAccessPolicyInterface
{
    public function __construct(
        private readonly \DateTimeImmutable $expiresAt,
    ) {}

    public function evaluate(AssetRequestContext $context): AssetAccessResult
    {
        if ($context->requestTime() <= $this->expiresAt) {
            return AssetAccessResult::allow();
        }

        return AssetAccessResult::deny('Expired');
    }
}
