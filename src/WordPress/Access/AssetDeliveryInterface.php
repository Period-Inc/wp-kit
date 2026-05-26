<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface AssetDeliveryInterface
{
    public function deliver(AssetRequestContext $context): AssetDeliveryResult;
}
