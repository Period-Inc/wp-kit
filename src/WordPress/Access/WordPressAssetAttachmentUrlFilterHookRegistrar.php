<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WordPressAssetAttachmentUrlFilterHookRegistrar
{
    /** @var callable(string, callable, int, int): void */
    private readonly mixed $addFilter;

    /**
     * @param callable(string, callable, int, int): void $addFilter
     */
    public function __construct(
        private readonly AssetAttachmentUrlFilter $filter,
        callable $addFilter,
    ) {
        $this->addFilter = $addFilter;
    }

    public function register(string $hook = 'wp_get_attachment_url', int $priority = 10): void
    {
        ($this->addFilter)($hook, [$this->filter, 'filter'], $priority, 2);
    }
}
