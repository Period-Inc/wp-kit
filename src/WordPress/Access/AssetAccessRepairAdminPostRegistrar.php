<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessRepairAdminPostRegistrar
{
    /** @var callable(string, callable, int): void */
    private readonly mixed $addAction;

    /** @param callable(string, callable, int): void $addAction */
    public function __construct(
        callable $addAction,
        private readonly AssetAccessRepairAdminPostAction $action,
    ) {
        $this->addAction = $addAction;
    }

    public function register(int $priority = 10): void
    {
        ($this->addAction)(
            'admin_post_' . $this->action->actionName(),
            [$this->action, 'handle'],
            $priority,
        );
    }
}
