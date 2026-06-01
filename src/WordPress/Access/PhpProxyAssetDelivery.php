<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class PhpProxyAssetDelivery implements AssetDeliveryInterface
{
    public function __construct(
        private readonly AssetAccessManager $manager,
        private readonly AssetStorageInterface $storage,
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

        $headers = ['X-Asset-Path' => $item->path()];

        if ($item->mimeType() !== null) {
            $headers['X-Asset-Mime'] = $item->mimeType();
        }

        return AssetDeliveryResult::ok(200, $headers);
    }
}
