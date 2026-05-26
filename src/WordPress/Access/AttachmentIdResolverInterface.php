<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface AttachmentIdResolverInterface
{
    public function resolve(string $assetPath): ?int;
}
