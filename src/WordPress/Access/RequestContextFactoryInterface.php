<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface RequestContextFactoryInterface
{
    public function create(string $requestUri): AssetRequestContext;
}
