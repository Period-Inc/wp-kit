<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetProtectedStateBadgeRenderer
{
    public function __construct(private readonly AssetAttachmentMetaReader $reader) {}

    public function render(int $attachmentId): string
    {
        $meta = $this->reader->read($attachmentId);

        if ($meta->isProtected()) {
            return sprintf(
                '<span class="%s">%s</span>',
                htmlspecialchars('period-asset-protected-badge', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars('Protected', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        return sprintf(
            '<span class="%s">%s</span>',
            htmlspecialchars('period-asset-public-badge', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars('Public', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }
}
