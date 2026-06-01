<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class CallbackAssetAccessPolicy implements AssetAccessPolicyInterface
{
    /** @var callable(AssetRequestContext): (bool|AssetAccessResult) */
    private readonly mixed $callback;

    /** @param callable(AssetRequestContext): (bool|AssetAccessResult) $callback */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function evaluate(AssetRequestContext $context): AssetAccessResult
    {
        $result = ($this->callback)($context);

        if ($result instanceof AssetAccessResult) {
            return $result;
        }

        return $result
            ? AssetAccessResult::allow()
            : AssetAccessResult::deny('Callback denied');
    }
}
