<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WordPressAttachmentAssetStorageFactory
{
    public function create(WpAttachmentResolver $resolver): AttachmentAssetStorage
    {
        return new AttachmentAssetStorage(
            fn(string $path) => $resolver->resolve($path)
        );
    }
}
