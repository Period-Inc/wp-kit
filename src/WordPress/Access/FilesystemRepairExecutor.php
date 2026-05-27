<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class FilesystemRepairExecutor
{
    public function __construct(private readonly FilesystemOperatorInterface $operator) {}

    /** @return AssetAccessRepairExecutionResult[] */
    public function execute(AssetAccessRepairPlan $plan): array
    {
        $results = [];

        foreach ($plan->actions() as $action) {
            $results[] = $this->executeAction($action);
        }

        return $results;
    }

    private function executeAction(AssetAccessRepairAction $action): AssetAccessRepairExecutionResult
    {
        return match ($action->type()) {
            AssetAccessRepairAction::TYPE_CREATE_DIRECTORY => new AssetAccessRepairExecutionResult(
                $this->operator->createDirectory($action->path()),
                $action->type(),
                $action->path(),
                'create directory executed',
            ),
            AssetAccessRepairAction::TYPE_PERMISSION_WARNING => new AssetAccessRepairExecutionResult(
                true,
                $action->type(),
                $action->path(),
                'permission warning requires manual review',
            ),
            AssetAccessRepairAction::TYPE_READABILITY_WARNING => new AssetAccessRepairExecutionResult(
                true,
                $action->type(),
                $action->path(),
                'readability warning requires manual review',
            ),
            default => new AssetAccessRepairExecutionResult(
                false,
                $action->type(),
                $action->path(),
                'unknown repair action',
            ),
        };
    }
}
