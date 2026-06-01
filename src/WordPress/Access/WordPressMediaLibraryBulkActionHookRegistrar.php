<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WordPressMediaLibraryBulkActionHookRegistrar
{
    /** @var callable */
    private readonly mixed $addFilter;

    /** @var callable */
    private readonly mixed $addAction;

    public function __construct(
        private readonly AssetBulkProtectionActionProvider $provider,
        callable $addFilter,
        callable $addAction,
    ) {
        $this->addFilter = $addFilter;
        $this->addAction = $addAction;
    }

    public function register(): void
    {
        ($this->addFilter)('bulk_actions-upload', [$this->provider, 'registerActions'], 10);

        ($this->addFilter)(
            'handle_bulk_actions-upload',
            function (string $redirectTo, string $action, array $postIds): string {
                $this->provider->handleAction($action, array_map('intval', $postIds));
                return $redirectTo;
            },
            10,
            3,
        );
    }
}
