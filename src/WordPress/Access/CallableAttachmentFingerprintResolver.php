<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class CallableAttachmentFingerprintResolver implements AttachmentFingerprintResolverInterface
{
    /** @var callable(int): ?string */
    private readonly mixed $resolver;

    /** @param callable(int): ?string $resolver */
    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public function resolve(int $attachmentId): ?string
    {
        return ($this->resolver)($attachmentId);
    }
}
