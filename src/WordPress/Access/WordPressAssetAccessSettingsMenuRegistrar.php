<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAccessSettingsMenuRegistrar
{
    /** @var callable */
    private readonly mixed $addOptionsPage;

    /** @param callable $addOptionsPage */
    public function __construct(
        private readonly WordPressAssetAccessSettingsPage $page,
        callable $addOptionsPage,
    ) {
        $this->addOptionsPage = $addOptionsPage;
    }

    public function register(): void
    {
        ($this->addOptionsPage)(
            'Asset Access Control',
            'Asset Access',
            'manage_options',
            'period-asset-access',
            function (): void {
                echo $this->page->render();
            },
        );
    }
}
