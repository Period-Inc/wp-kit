<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

interface AttachmentFingerprintResolverInterface
{
    public function resolve(int $attachmentId): ?string;
}
