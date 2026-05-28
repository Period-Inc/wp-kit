<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class WordPressAssetAccessSettingsPage
{
    /** @var callable(string): bool */
    private readonly mixed $currentUserCan;

    /** @var callable(): array<string, string> */
    private readonly mixed $getRoles;

    /**
     * @param callable(string): bool             $currentUserCan
     * @param callable(): array<string, string>  $getRoles
     */
    public function __construct(
        private readonly AssetAccessSettingsRepositoryInterface $repository,
        private readonly AssetAccessSettingsPageRenderer $renderer,
        private readonly AssetAccessSettingsFormHandler $handler,
        callable $currentUserCan,
        callable $getRoles,
        private readonly ?AssetAccessHealthSettingsSection $healthSection = null,
        private readonly ?AssetAccessRepairSection $repairSection = null,
        private readonly ?AssetAccessRepairExecutionController $repairExecutionController = null,
        private readonly ?AssetAccessRepairExecutionRenderer $repairExecutionRenderer = null,
        private readonly ?AssetAccessRepairNonceFieldRenderer $repairNonceFieldRenderer = null,
    ) {
        $this->currentUserCan = $currentUserCan;
        $this->getRoles       = $getRoles;
    }

    /** @var AssetAccessRepairExecutionResult[] */
    private array $repairExecutionResults = [];

    public function render(): string
    {
        if (!($this->currentUserCan)('manage_options')) {
            return '';
        }

        $settings       = $this->repository->get();
        $availableRoles = ($this->getRoles)();
        $html           = $this->renderer->render($settings, $availableRoles);

        if ($this->healthSection !== null) {
            $html .= $this->healthSection->render();
        }

        if ($this->repairSection !== null) {
            $html .= $this->repairSection->render();
        }

        if ($this->repairExecutionController !== null || $this->repairNonceFieldRenderer !== null) {
            $html .= $this->renderRepairExecutionForm();
        }

        if ($this->repairExecutionController !== null && $this->repairExecutionRenderer !== null) {
            $html .= $this->repairExecutionRenderer->render($this->repairExecutionResults);
        }

        return $html;
    }

    /** @param array<string,mixed> $postData */
    public function handlePost(array $postData): ?AssetAccessSettings
    {
        if (!($this->currentUserCan)('manage_options')) {
            return null;
        }

        if (isset($postData['asset_access_repair_execute']) && $postData['asset_access_repair_execute'] === '1') {
            $this->repairExecutionResults = $this->repairExecutionController?->execute() ?? [];

            return $this->repository->get();
        }

        return $this->handler->handle($postData);
    }

    private function renderRepairExecutionForm(): string
    {
        return '<form method="post" class="period-asset-access-repair-execute">'
            . '<input type="hidden" name="asset_access_repair_execute" value="1">'
            . $this->renderRepairNonceField()
            . '<button type="submit">Execute repair plan</button>'
            . '</form>';
    }

    private function renderRepairNonceField(): string
    {
        return $this->repairNonceFieldRenderer?->render()
            ?? '<input type="hidden" name="asset_access_repair_nonce" value="">';
    }
}
