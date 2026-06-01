<?php

declare(strict_types=1);

namespace Period\WpKit\WordPress\Access;

final class AssetAccessSettingsPageRenderer
{
    /**
     * @param array<string, string> $availableRoles  role slug => display label
     */
    public function render(AssetAccessSettings $settings, array $availableRoles): string
    {
        return $this->renderEnabledField($settings)
            . $this->renderRolesField($settings, $availableRoles)
            . $this->renderVisibilityField($settings)
            . $this->renderPrivateAssetRootField($settings);
    }

    private function renderEnabledField(AssetAccessSettings $settings): string
    {
        $checked = $settings->isEnabled() ? ' checked' : '';
        $name    = htmlspecialchars('period_asset_access[enabled]', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $label   = htmlspecialchars('Enable Asset Access Control', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<p><label><input type="checkbox" name="%s" value="1"%s> %s</label></p>',
            $name,
            $checked,
            $label,
        );
    }

    /** @param array<string, string> $availableRoles */
    private function renderRolesField(AssetAccessSettings $settings, array $availableRoles): string
    {
        $protectedRoles = $settings->protectedRoles();
        $name           = htmlspecialchars('period_asset_access[protected_roles][]', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $items          = '';

        foreach ($availableRoles as $slug => $label) {
            $checked      = in_array($slug, $protectedRoles, true) ? ' checked' : '';
            $escapedValue = htmlspecialchars((string) $slug, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $escapedLabel = htmlspecialchars((string) $label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $items .= sprintf(
                '<label><input type="checkbox" name="%s" value="%s"%s> %s</label>',
                $name,
                $escapedValue,
                $checked,
                $escapedLabel,
            );
        }

        return '<p>' . $items . '</p>';
    }

    private function renderVisibilityField(AssetAccessSettings $settings): string
    {
        $name    = htmlspecialchars('period_asset_access[default_visibility]', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $current = $settings->defaultVisibility();

        $options = [
            AssetAccessSettings::VISIBILITY_PUBLIC  => 'Public',
            AssetAccessSettings::VISIBILITY_PRIVATE => 'Private',
        ];

        $optionsHtml = '';
        foreach ($options as $value => $label) {
            $selected     = $value === $current ? ' selected' : '';
            $escapedValue = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $escapedLabel = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $optionsHtml .= sprintf(
                '<option value="%s"%s>%s</option>',
                $escapedValue,
                $selected,
                $escapedLabel,
            );
        }

        return sprintf('<p><select name="%s">%s</select></p>', $name, $optionsHtml);
    }

    private function renderPrivateAssetRootField(AssetAccessSettings $settings): string
    {
        $name  = htmlspecialchars('period_asset_access[private_asset_root]', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $label = htmlspecialchars('Private asset root', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $value = htmlspecialchars((string) $settings->privateAssetRoot(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<p><label>%s <input type="text" name="%s" value="%s"></label></p>',
            $label,
            $name,
            $value,
        );
    }
}
