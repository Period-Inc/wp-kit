<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WordPressAssetAccessBootstrap
{
    /** @var callable(string): AssetRequestContext */
    private readonly mixed $requestContextFactory;

    /** @param callable(string): AssetRequestContext $requestContextFactory */
    public function __construct(
        private readonly WordPressAssetAccessKernel $kernel,
        callable $requestContextFactory,
    ) {
        $this->requestContextFactory = $requestContextFactory;
    }

    public function boot(string $requestUri): ?AssetDeliveryResult
    {
        $context = ($this->requestContextFactory)($requestUri);

        return $this->kernel->handle($context);
    }
}
