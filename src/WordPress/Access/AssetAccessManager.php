<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessManager
{
    public function __construct(
        private readonly AssetAccessPolicyInterface $policy,
    ) {}

    public function authorize(AssetRequestContext $context): AssetAccessResult
    {
        return $this->policy->evaluate($context);
    }
}
