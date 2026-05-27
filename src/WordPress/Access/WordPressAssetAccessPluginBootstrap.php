<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAccessPluginBootstrap
{
    /** @var callable(): string */
    private readonly mixed $requestUriResolver;

    /** @param callable(): string $requestUriResolver */
    public function __construct(
        private readonly WordPressAssetAccessRuntimeDefaultsFactory $runtimeDefaultsFactory,
        private readonly ?WordPressAssetAccessBootstrapFactory $bootstrapFactory,
        callable $requestUriResolver,
    ) {
        $this->requestUriResolver = $requestUriResolver;
    }

    public function boot(): WordPressAssetAccessRuntimeInstaller
    {
        $bootstrapFactory = $this->bootstrapFactory
            ?? new WordPressAssetAccessBootstrapFactory($this->runtimeDefaultsFactory->create());

        $installer = $bootstrapFactory->createInstaller($this->requestUriResolver);
        $installer->install();

        return $installer;
    }
}
