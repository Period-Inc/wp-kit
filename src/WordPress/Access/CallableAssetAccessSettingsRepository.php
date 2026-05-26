<?php

declare(strict_types=1);

namespace Period\WpFramework\WordPress\Access;

final class CallableAssetAccessSettingsRepository implements AssetAccessSettingsRepositoryInterface
{
    private const OPTION_KEY = 'period_asset_access_settings';

    /** @var callable(string, mixed): mixed */
    private readonly mixed $getOption;

    /** @var callable(string, mixed): void */
    private readonly mixed $updateOption;

    /**
     * @param callable(string, mixed): mixed $getOption
     * @param callable(string, mixed): void  $updateOption
     */
    public function __construct(callable $getOption, callable $updateOption)
    {
        $this->getOption    = $getOption;
        $this->updateOption = $updateOption;
    }

    public function get(): AssetAccessSettings
    {
        $raw = ($this->getOption)(self::OPTION_KEY, []);

        if (!is_array($raw)) {
            return AssetAccessSettings::default();
        }

        $roles = array_values(array_filter(
            (array) ($raw['protected_roles'] ?? []),
            fn(mixed $v): bool => is_string($v),
        ));

        return new AssetAccessSettings(
            enabled:           (bool) ($raw['enabled'] ?? false),
            protectedRoles:    $roles,
            defaultVisibility: (string) ($raw['default_visibility'] ?? AssetAccessSettings::VISIBILITY_PUBLIC),
        );
    }

    public function save(AssetAccessSettings $settings): void
    {
        ($this->updateOption)(self::OPTION_KEY, [
            'enabled'            => $settings->isEnabled(),
            'protected_roles'    => $settings->protectedRoles(),
            'default_visibility' => $settings->defaultVisibility(),
        ]);
    }
}
