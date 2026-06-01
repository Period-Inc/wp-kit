<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class WordPressAssetAccessSettingsSaveHookRegistrar
{
    /** @var callable(string, callable, int): void */
    private readonly mixed $addAction;

    /** @param callable(string, callable, int): void $addAction */
    public function __construct(
        private readonly WordPressAssetAccessSettingsSaveController $controller,
        callable $addAction,
    ) {
        $this->addAction = $addAction;
    }

    public function register(
        string $hook = 'admin_post_period_asset_access_save',
        int $priority = 10,
    ): void {
        ($this->addAction)($hook, [$this->controller, 'handle'], $priority);
    }
}
