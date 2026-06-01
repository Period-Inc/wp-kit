<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class MemoryAssetResponseEmitter implements AssetResponseEmitterInterface
{
    public function emit(AssetDeliveryResult $result): AssetEmitResult
    {
        return new AssetEmitResult(
            emitted: true,
            statusCode: $result->statusCode(),
            headers: $result->headers(),
            body: $result->body(),
            redirectUrl: $result->redirectUrl(),
        );
    }
}
