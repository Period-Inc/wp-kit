<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessRepairExecutionController
{
    /** @var callable(string): bool */
    private readonly mixed $nonceVerifier;

    /** @param callable(string): bool $nonceVerifier */
    public function __construct(
        private readonly FilesystemRepairPlanner $planner,
        private readonly FilesystemRepairExecutor $executor,
        callable $nonceVerifier,
        private readonly AssetAccessRepairRequest $request,
    ) {
        $this->nonceVerifier = $nonceVerifier;
    }

    /** @return AssetAccessRepairExecutionResult[] */
    public function execute(): array
    {
        if (!$this->request->confirmed()) {
            return [];
        }

        if (!$this->request->currentUserCanManage()) {
            return [];
        }

        if (!($this->nonceVerifier)($this->request->nonce())) {
            return [];
        }

        return $this->executor->execute($this->planner->plan());
    }
}
