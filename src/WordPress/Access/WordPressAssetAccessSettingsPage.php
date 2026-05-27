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
    ) {
        $this->currentUserCan = $currentUserCan;
        $this->getRoles       = $getRoles;
    }

    public function render(): string
    {
        if (!($this->currentUserCan)('manage_options')) {
            return '';
        }

        $settings       = $this->repository->get();
        $availableRoles = ($this->getRoles)();
        $html           = $this->renderer->render($settings, $availableRoles);

        if ($this->healthSection === null) {
            return $html;
        }

        return $html . $this->healthSection->render();
    }

    /** @param array<string,mixed> $postData */
    public function handlePost(array $postData): ?AssetAccessSettings
    {
        if (!($this->currentUserCan)('manage_options')) {
            return null;
        }

        return $this->handler->handle($postData);
    }
}
