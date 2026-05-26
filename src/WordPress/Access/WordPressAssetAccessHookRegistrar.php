<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAccessHookRegistrar
{
    /** @var callable(string, callable, int): void */
    private readonly mixed $addAction;

    /** @param callable(string, callable, int): void $addAction */
    public function __construct(
        private readonly WordPressAssetAccessController $controller,
        callable $addAction,
    ) {
        $this->addAction = $addAction;
    }

    public function register(string $hook = 'init', int $priority = 0): void
    {
        ($this->addAction)($hook, [$this->controller, 'handle'], $priority);
    }
}
