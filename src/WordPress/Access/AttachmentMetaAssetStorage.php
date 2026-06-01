<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AttachmentMetaAssetStorage implements AssetStorageInterface
{
    /** @var callable(string): ?int */
    private readonly mixed $attachmentIdResolver;

    /**
     * @param callable(string): ?int $attachmentIdResolver
     */
    public function __construct(
        private readonly AssetAttachmentMetaReader $reader,
        callable $attachmentIdResolver,
    ) {
        $this->attachmentIdResolver = $attachmentIdResolver;
    }

    public function find(string $assetPath): ?AssetStorageItem
    {
        $attachmentId = ($this->attachmentIdResolver)($assetPath);

        if ($attachmentId === null) {
            return null;
        }

        $meta = $this->reader->read($attachmentId);

        if (!$meta->isProtected()) {
            return null;
        }

        $protectedPath = $meta->protectedPath();

        if ($protectedPath === null || $protectedPath === '') {
            return null;
        }

        if ($protectedPath !== $assetPath) {
            return null;
        }

        return new AssetStorageItem(
            path: $assetPath,
            url: $meta->deliveryUrl(),
            mimeType: null,
            size: null,
            lastModified: null,
            metadata: [
                'attachmentId' => $attachmentId,
                'protected'    => true,
                'protectedPath' => $protectedPath,
                'deliveryUrl'  => $meta->deliveryUrl(),
            ],
        );
    }
}
