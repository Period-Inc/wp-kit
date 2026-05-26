<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface AssetResponseEmitterInterface
{
    public function emit(AssetDeliveryResult $result): AssetEmitResult;
}
