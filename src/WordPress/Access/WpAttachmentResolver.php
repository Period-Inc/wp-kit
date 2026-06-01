<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WpAttachmentResolver
{
    /** @var callable(string): ?array<string, mixed> */
    private readonly mixed $attachmentLookup;

    /** @param callable(string): ?array<string, mixed> $attachmentLookup */
    public function __construct(callable $attachmentLookup)
    {
        $this->attachmentLookup = $attachmentLookup;
    }

    /** @return ?array<string, mixed> */
    public function resolve(string $assetPath): ?array
    {
        return ($this->attachmentLookup)($assetPath);
    }
}
