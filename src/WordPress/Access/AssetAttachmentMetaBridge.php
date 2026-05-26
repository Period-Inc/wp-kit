<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAttachmentMetaBridge
{
    /** @var array<string,mixed>|null */
    private ?array $pendingUpload = null;

    public function __construct(
        private readonly AssetAttachmentMetaUpdater $updater,
    ) {}

    /**
     * @param array<string,mixed> $upload
     * @return array<string,mixed>
     */
    public function rememberUpload(array $upload): array
    {
        $this->pendingUpload = $upload;

        return $upload;
    }

    public function updateAttachment(int $attachmentId): void
    {
        if ($this->pendingUpload === null) {
            return;
        }

        $upload              = $this->pendingUpload;
        $this->pendingUpload = null;

        $this->updater->update($attachmentId, $upload);
    }
}
