<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WordPressAssetUploadHookRegistrar
{
    /** @var callable(string, callable, int): void */
    private readonly mixed $addFilter;

    /**
     * @param callable(string, callable, int): void $addFilter
     */
    public function __construct(
        private readonly AssetUploadInterceptor $interceptor,
        callable $addFilter,
    ) {
        $this->addFilter = $addFilter;
    }

    public function register(string $hook = 'wp_handle_upload', int $priority = 10): void
    {
        ($this->addFilter)($hook, [$this->interceptor, 'intercept'], $priority);
    }
}
