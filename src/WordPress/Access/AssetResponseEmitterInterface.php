<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface AssetResponseEmitterInterface
{
    public function emit(AssetDeliveryResult $result): AssetEmitResult;
}
