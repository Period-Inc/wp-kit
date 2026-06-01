<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

interface AttachmentFingerprintResolverInterface
{
    public function resolve(int $attachmentId): ?string;
}
