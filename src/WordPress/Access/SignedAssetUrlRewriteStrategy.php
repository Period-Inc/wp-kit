<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

use DateTimeImmutable;

final class SignedAssetUrlRewriteStrategy implements AssetUrlRewriteStrategyInterface
{
    public function __construct(
        private readonly SignedAssetUrlGenerator $generator,
        private readonly DateTimeImmutable $expiresAt,
    ) {}

    public function rewrite(string $originalUrl, string $protectedPath): string
    {
        return $this->generator->generate($protectedPath, $this->expiresAt);
    }
}
