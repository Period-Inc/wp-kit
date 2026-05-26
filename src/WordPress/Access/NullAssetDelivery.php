<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class NullAssetDelivery implements AssetDeliveryInterface
{
    public function deliver(AssetRequestContext $context): AssetDeliveryResult
    {
        return AssetDeliveryResult::deny(501, 'Asset delivery not implemented');
    }
}
