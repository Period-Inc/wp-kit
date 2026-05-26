<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface RequestContextFactoryInterface
{
    public function create(string $requestUri): AssetRequestContext;
}
