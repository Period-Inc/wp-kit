<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class CompositeAssetAccessPolicy implements AssetAccessPolicyInterface
{
    /** @param AssetAccessPolicyInterface[] $policies */
    public function __construct(
        private readonly array $policies,
        private readonly CompositeMode $mode,
    ) {}

    public function evaluate(AssetRequestContext $context): AssetAccessResult
    {
        return match ($this->mode) {
            CompositeMode::All => $this->evaluateAll($context),
            CompositeMode::Any => $this->evaluateAny($context),
        };
    }

    private function evaluateAll(AssetRequestContext $context): AssetAccessResult
    {
        $reasons = [];

        foreach ($this->policies as $policy) {
            $result = $policy->evaluate($context);
            if (!$result->allowed()) {
                $reasons[] = $result->reason() ?? '';
            }
        }

        if ($reasons === []) {
            return AssetAccessResult::allow();
        }

        return AssetAccessResult::deny(implode('; ', $reasons));
    }

    private function evaluateAny(AssetRequestContext $context): AssetAccessResult
    {
        $reasons = [];

        foreach ($this->policies as $policy) {
            $result = $policy->evaluate($context);
            if ($result->allowed()) {
                return AssetAccessResult::allow();
            }
            $reasons[] = $result->reason() ?? '';
        }

        return AssetAccessResult::deny(
            $reasons !== [] ? implode('; ', $reasons) : 'No policies allowed'
        );
    }
}
