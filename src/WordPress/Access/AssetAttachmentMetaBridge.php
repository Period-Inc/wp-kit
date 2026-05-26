<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAttachmentMetaBridge
{
    /** @var array<string, array<string,mixed>> */
    private array $pendingUploads = [];

    public function __construct(
        private readonly AssetAttachmentMetaUpdater $updater,
        private readonly AttachmentFingerprintResolverInterface $fingerprintResolver,
        private readonly AssetUploadFingerprintGenerator $fingerprintGenerator,
    ) {}

    /**
     * @param array<string,mixed> $upload
     * @return array<string,mixed>
     */
    public function rememberUpload(array $upload): array
    {
        $fingerprint = $this->fingerprintGenerator->generate($upload);

        $this->pendingUploads[$fingerprint] = $upload;

        $upload['_period_upload_fingerprint'] = $fingerprint;

        return $upload;
    }

    public function updateAttachment(int $attachmentId): void
    {
        $fingerprint = $this->fingerprintResolver->resolve($attachmentId);

        if ($fingerprint === null) {
            return;
        }

        if (!array_key_exists($fingerprint, $this->pendingUploads)) {
            return;
        }

        $upload = $this->pendingUploads[$fingerprint];
        unset($this->pendingUploads[$fingerprint]);

        $this->updater->update($attachmentId, $upload);
    }
}
