<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class StreamedAssetDelivery implements AssetDeliveryInterface
{
    public function __construct(
        private readonly AssetAccessManager $manager,
        private readonly AssetStorageInterface $storage,
        private readonly FileStreamInterface $stream,
    ) {}

    public function deliver(AssetRequestContext $context): AssetDeliveryResult
    {
        $item = $this->storage->find($context->assetPath());

        if ($item === null) {
            return AssetDeliveryResult::deny(404);
        }

        $authResult = $this->manager->authorize($context);

        if (!$authResult->allowed()) {
            return AssetDeliveryResult::deny(403);
        }

        if (!$this->stream->exists($item->path())) {
            return AssetDeliveryResult::deny(500);
        }

        try {
            $contents = $this->stream->read($item->path());
        } catch (\Throwable) {
            return AssetDeliveryResult::deny(500);
        }

        $headers = [];
        if ($item->mimeType() !== null) {
            $headers['Content-Type'] = $item->mimeType();
        }

        return AssetDeliveryResult::ok(200, $headers, $contents);
    }
}
