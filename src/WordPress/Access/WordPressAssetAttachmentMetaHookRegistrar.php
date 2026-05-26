<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAttachmentMetaHookRegistrar
{
    /** @var callable(string, callable, int): void */
    private readonly mixed $addAction;

    /**
     * @param callable(string, callable, int): void $addAction
     */
    public function __construct(
        private readonly AssetAttachmentMetaUpdater $updater,
        callable $addAction,
    ) {
        $this->addAction = $addAction;
    }

    public function register(string $hook = 'add_attachment', int $priority = 10): void
    {
        ($this->addAction)($hook, [$this->updater, 'update'], $priority);
    }
}
