<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class HttpAssetResponseEmitter implements AssetResponseEmitterInterface
{
    public function __construct(
        private readonly HttpEmitterInterface $emitter,
    ) {}

    public function emit(AssetDeliveryResult $result): AssetEmitResult
    {
        $this->emitter->status($result->statusCode());

        foreach ($result->headers() as $name => $value) {
            $this->emitter->header($name, $value);
        }

        if ($result->redirectUrl() !== null) {
            $this->emitter->redirect($result->redirectUrl(), $result->statusCode());
        } else {
            $this->emitter->body($result->body());
        }

        return new AssetEmitResult(
            emitted: true,
            statusCode: $result->statusCode(),
            headers: $result->headers(),
            body: $result->body(),
            redirectUrl: $result->redirectUrl(),
        );
    }
}
