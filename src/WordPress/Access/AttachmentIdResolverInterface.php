<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface AttachmentIdResolverInterface
{
    public function resolve(string $assetPath): ?int;
}
