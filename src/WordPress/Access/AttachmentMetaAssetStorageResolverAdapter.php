<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AttachmentMetaAssetStorageResolverAdapter
{
    public function __construct(private readonly AttachmentIdResolverInterface $resolver) {}

    /** @return callable(string): ?int */
    public function resolver(): callable
    {
        return fn(string $assetPath): ?int => $this->resolver->resolve($assetPath);
    }
}
