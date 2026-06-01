<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessRepairExecutionResult
{
    public function __construct(
        private readonly bool $success,
        private readonly string $actionType,
        private readonly string $path,
        private readonly string $message,
    ) {}

    public function success(): bool
    {
        return $this->success;
    }

    public function actionType(): string
    {
        return $this->actionType;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function message(): string
    {
        return $this->message;
    }
}
