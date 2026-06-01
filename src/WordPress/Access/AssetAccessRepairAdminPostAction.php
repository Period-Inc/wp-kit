<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessRepairAdminPostAction
{
    /** @var callable(): string */
    private readonly mixed $redirectUrlResolver;

    /** @param callable(): string $redirectUrlResolver */
    public function __construct(
        private readonly string $actionName,
        private readonly AssetAccessRepairExecutionController $controller,
        callable $redirectUrlResolver,
    ) {
        $this->redirectUrlResolver = $redirectUrlResolver;
    }

    public function actionName(): string
    {
        return $this->actionName;
    }

    public function handle(): string
    {
        $this->controller->execute();

        return (string) ($this->redirectUrlResolver)();
    }
}
