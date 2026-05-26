<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class DefaultRequestContextFactory implements RequestContextFactoryInterface
{
    /** @var callable(string): string */
    private readonly mixed $assetUrlResolver;

    /**
     * @param string[] $currentUserRoles
     * @param callable(string): string $assetUrlResolver
     */
    public function __construct(
        private readonly int $currentUserId,
        private readonly array $currentUserRoles,
        callable $assetUrlResolver,
    ) {
        $this->assetUrlResolver = $assetUrlResolver;
    }

    public function create(string $requestUri): AssetRequestContext
    {
        $assetPath = explode('?', $requestUri, 2)[0];
        $assetUrl  = ($this->assetUrlResolver)($requestUri);

        return new AssetRequestContext(
            assetPath: $assetPath,
            assetUrl: $assetUrl,
            currentUserId: $this->currentUserId,
            currentUserRoles: $this->currentUserRoles,
            requestTime: new \DateTimeImmutable(),
        );
    }
}
