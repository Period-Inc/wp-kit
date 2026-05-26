<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAttachmentImageSrcFilter
{
    public function __construct(
        private readonly AssetAttachmentMetaReader $reader,
        private readonly AssetUrlRewriteStrategyInterface $strategy,
    ) {}

    /** @param array<mixed>|false $image */
    public function filter(array|false $image, int $attachmentId): array|false
    {
        if ($image === false) {
            return false;
        }

        $meta = $this->reader->read($attachmentId);

        if (!$meta->isProtected()) {
            return $image;
        }

        $protectedPath = $meta->protectedPath();

        if ($protectedPath === null || $protectedPath === '') {
            return $image;
        }

        $image[0] = $this->strategy->rewrite((string) ($image[0] ?? ''), $protectedPath);

        return $image;
    }
}
