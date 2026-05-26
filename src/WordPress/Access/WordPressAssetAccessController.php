<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAccessController
{
    /** @var callable(): string */
    private readonly mixed $requestUriResolver;

    /** @param callable(): string $requestUriResolver */
    public function __construct(
        private readonly WordPressAssetAccessBootstrap $bootstrap,
        private readonly AssetResponseEmitterInterface $emitter,
        callable $requestUriResolver,
    ) {
        $this->requestUriResolver = $requestUriResolver;
    }

    public function handle(): ?AssetEmitResult
    {
        $requestUri = ($this->requestUriResolver)();

        $deliveryResult = $this->bootstrap->boot($requestUri);

        if ($deliveryResult === null) {
            return null;
        }

        return $this->emitter->emit($deliveryResult);
    }
}
