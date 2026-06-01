<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WordPressAssetAccessSettingsSaveController
{
    /** @var callable(string): void */
    private readonly mixed $redirect;

    /** @var callable(string): string */
    private readonly mixed $adminUrl;

    /**
     * @param callable(string): void   $redirect
     * @param callable(string): string $adminUrl
     */
    public function __construct(
        private readonly WordPressAssetAccessSettingsPage $page,
        callable $redirect,
        callable $adminUrl,
    ) {
        $this->redirect = $redirect;
        $this->adminUrl = $adminUrl;
    }

    /** @param array<string,mixed> $postData */
    public function handle(array $postData): void
    {
        $this->page->handlePost($postData);

        ($this->redirect)(
            ($this->adminUrl)('options-general.php?page=period-asset-access&updated=1')
        );
    }
}
