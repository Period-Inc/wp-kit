<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAccessKernel
{
    public function __construct(
        private readonly AssetRequestMatcher $matcher,
        private readonly AssetAccessManager $manager,
        private readonly AssetDeliveryInterface $delivery,
        private readonly AssetStorageInterface $storage,
    ) {}

    public function handle(AssetRequestContext $context): ?AssetDeliveryResult
    {
        $urlPath = parse_url($context->assetUrl(), PHP_URL_PATH) ?? $context->assetPath();

        if (!$this->matcher->matches($urlPath)) {
            return null;
        }

        $item = $this->storage->find($context->assetPath());

        if ($item === null) {
            return AssetDeliveryResult::deny(404);
        }

        $authResult = $this->manager->authorize($context);

        if (!$authResult->allowed()) {
            return AssetDeliveryResult::deny(403);
        }

        return $this->delivery->deliver($context);
    }
}
