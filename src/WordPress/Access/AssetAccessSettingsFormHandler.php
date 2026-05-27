<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class AssetAccessSettingsFormHandler
{
    public function __construct(
        private readonly AssetAccessSettingsRepositoryInterface $repository,
    ) {}

    /** @param array<string,mixed> $postData */
    public function handle(array $postData): AssetAccessSettings
    {
        $raw = isset($postData['period_asset_access']) && is_array($postData['period_asset_access'])
            ? $postData['period_asset_access']
            : [];

        $enabled    = isset($raw['enabled']) && $raw['enabled'] === '1';
        $roles      = $this->normalizeRoles($raw['protected_roles'] ?? []);
        $visibility = $this->normalizeVisibility((string) ($raw['default_visibility'] ?? ''));
        $privateAssetRoot = $this->normalizePrivateAssetRoot($raw['private_asset_root'] ?? null);

        $settings = new AssetAccessSettings($enabled, $roles, $visibility, $privateAssetRoot);
        $this->repository->save($settings);

        return $settings;
    }

    /** @return string[] */
    private function normalizeRoles(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $result = [];
        foreach ($raw as $item) {
            if (!is_string($item)) {
                continue;
            }
            $trimmed = trim($item);
            if ($trimmed !== '') {
                $result[] = $trimmed;
            }
        }

        return array_values($result);
    }

    private function normalizeVisibility(string $raw): string
    {
        return match ($raw) {
            AssetAccessSettings::VISIBILITY_PUBLIC,
            AssetAccessSettings::VISIBILITY_PRIVATE => $raw,
            default                                 => AssetAccessSettings::VISIBILITY_PUBLIC,
        };
    }

    private function normalizePrivateAssetRoot(mixed $raw): ?string
    {
        if (!is_string($raw)) {
            return null;
        }

        $trimmed = trim($raw);

        return $trimmed === '' ? null : $trimmed;
    }
}
