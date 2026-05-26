<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetRequestMatcher
{
    /** @param string[] $protectedPrefixes */
    public function __construct(
        private readonly array $protectedPrefixes = ['/wp-content/uploads/'],
    ) {}

    public function matches(string $requestUri): bool
    {
        $path = explode('?', $requestUri, 2)[0];

        foreach ($this->protectedPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
