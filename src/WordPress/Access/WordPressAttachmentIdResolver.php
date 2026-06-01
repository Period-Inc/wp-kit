<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WordPressAttachmentIdResolver implements AttachmentIdResolverInterface
{
    /** @var callable(string): int|false */
    private readonly mixed $attachmentUrlToPostId;

    /** @var callable(): string */
    private readonly mixed $uploadsBaseUrlResolver;

    /**
     * @param callable(string): int|false $attachmentUrlToPostId
     * @param callable(): string          $uploadsBaseUrlResolver
     */
    public function __construct(callable $attachmentUrlToPostId, callable $uploadsBaseUrlResolver)
    {
        $this->attachmentUrlToPostId  = $attachmentUrlToPostId;
        $this->uploadsBaseUrlResolver = $uploadsBaseUrlResolver;
    }

    public function resolve(string $assetPath): ?int
    {
        $url = $this->toAbsoluteUrl($assetPath);
        $id  = ($this->attachmentUrlToPostId)($url);

        if (!$id || $id <= 0) {
            return null;
        }

        return $id;
    }

    private function toAbsoluteUrl(string $assetPath): string
    {
        if (str_starts_with($assetPath, 'http://') || str_starts_with($assetPath, 'https://')) {
            return $assetPath;
        }

        return rtrim(($this->uploadsBaseUrlResolver)(), '/') . '/' . ltrim($assetPath, '/');
    }
}
