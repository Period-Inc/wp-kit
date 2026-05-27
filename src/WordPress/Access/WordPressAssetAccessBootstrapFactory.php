<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAccessBootstrapFactory
{
    public function __construct(
        private readonly WordPressAssetAccessRuntimeDefaults $defaults,
    ) {}

    public function createFactory(): WordPressAssetAccessApplicationFactory
    {
        return new WordPressAssetAccessApplicationFactory(
            new CallableAssetAccessSettingsRepository(
                $this->defaults->getOption(),
                $this->defaults->updateOption(),
            ),
            new AssetAccessPolicyFactory(),
            new InMemoryAssetStorage([]),
            new NullAssetDelivery(),
            new AssetRequestMatcher(['/wp-content/uploads/']),
            new DefaultRequestContextFactory(
                0,
                [],
                static fn(string $requestUri): string => $requestUri,
            ),
            new MemoryAssetResponseEmitter(),
            $this->defaults->addAction(),
            $this->defaults->addFilter(),
            getMeta: $this->defaults->getMeta(),
            updateMeta: $this->defaults->updateMeta(),
            filesystemInspector: $this->defaults->filesystemInspector(),
            privateAssetRoot: $this->defaults->privateAssetRoot(),
        );
    }

    public function createInstaller(): WordPressAssetAccessRuntimeInstaller
    {
        return $this->createFactory()->createRuntimeInstaller(
            static fn(): string => '',
        );
    }
}
